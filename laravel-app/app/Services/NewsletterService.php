<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsletterService
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Subscribe user to newsletter
     */
    public function subscribe(string $email, array $preferences = []): array
    {
        try {
            // Check if email already exists
            $existingSubscription = DB::table('newsletter_subscriptions')
                ->where('email', $email)
                ->first();

            if ($existingSubscription) {
                if ($existingSubscription->is_active) {
                    return [
                        'success' => false,
                        'message' => 'Email is already subscribed to the newsletter'
                    ];
                } else {
                    // Reactivate subscription
                    DB::table('newsletter_subscriptions')
                        ->where('email', $email)
                        ->update([
                            'is_active' => true,
                            'preferences' => json_encode($preferences),
                            'updated_at' => now()
                        ]);

                    return [
                        'success' => true,
                        'message' => 'Newsletter subscription reactivated successfully'
                    ];
                }
            }

            // Create new subscription
            $subscriptionId = DB::table('newsletter_subscriptions')->insertGetId([
                'email' => $email,
                'preferences' => json_encode($preferences),
                'is_active' => true,
                'subscribed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Send welcome email
            $this->sendWelcomeEmail($email);

            Log::info('Newsletter subscription created', [
                'email' => $email,
                'subscription_id' => $subscriptionId
            ]);

            return [
                'success' => true,
                'message' => 'Successfully subscribed to newsletter',
                'subscription_id' => $subscriptionId
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter subscription failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to subscribe to newsletter'
            ];
        }
    }

    /**
     * Unsubscribe user from newsletter
     */
    public function unsubscribe(string $email, string $token = null): array
    {
        try {
            $updated = DB::table('newsletter_subscriptions')
                ->where('email', $email)
                ->update([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::info('Newsletter unsubscription', ['email' => $email]);

                return [
                    'success' => true,
                    'message' => 'Successfully unsubscribed from newsletter'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Email not found in newsletter subscriptions'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to unsubscribe from newsletter'
            ];
        }
    }

    /**
     * Update subscription preferences
     */
    public function updatePreferences(string $email, array $preferences): array
    {
        try {
            $updated = DB::table('newsletter_subscriptions')
                ->where('email', $email)
                ->where('is_active', true)
                ->update([
                    'preferences' => json_encode($preferences),
                    'updated_at' => now()
                ]);

            if ($updated) {
                return [
                    'success' => true,
                    'message' => 'Newsletter preferences updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Active subscription not found for this email'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Newsletter preferences update failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update newsletter preferences'
            ];
        }
    }

    /**
     * Send newsletter to all active subscribers
     */
    public function sendNewsletter(string $subject, string $content, array $filters = []): array
    {
        try {
            // Get active subscribers
            $query = DB::table('newsletter_subscriptions')
                ->where('is_active', true);

            // Apply filters if provided
            if (!empty($filters['preferences'])) {
                $query->where(function ($q) use ($filters) {
                    foreach ($filters['preferences'] as $preference) {
                        $q->orWhereJsonContains('preferences', $preference);
                    }
                });
            }

            $subscribers = $query->get();

            if ($subscribers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No active subscribers found'
                ];
            }

            // Prepare recipients array
            $recipients = $subscribers->map(function ($subscriber) {
                return [
                    'email' => $subscriber->email,
                    'name' => '' // Newsletter subscribers might not have names
                ];
            })->toArray();

            // Send newsletter
            $results = $this->emailService->sendNewsletterEmail(
                $recipients,
                $subject,
                $content,
                [
                    'newsletter_date' => now()->format('F j, Y'),
                    'app_name' => config('app.name')
                ]
            );

            // Log newsletter send
            DB::table('newsletter_campaigns')->insert([
                'subject' => $subject,
                'content' => $content,
                'recipients_count' => count($recipients),
                'sent_count' => $results['sent'],
                'failed_count' => $results['failed'],
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Newsletter sent', [
                'subject' => $subject,
                'total_recipients' => count($recipients),
                'sent' => $results['sent'],
                'failed' => $results['failed']
            ]);

            return [
                'success' => true,
                'message' => "Newsletter sent to {$results['sent']} subscribers",
                'stats' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Newsletter sending failed', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send newsletter'
            ];
        }
    }

    /**
     * Get newsletter statistics
     */
    public function getStats(): array
    {
        try {
            $totalSubscribers = DB::table('newsletter_subscriptions')
                ->where('is_active', true)
                ->count();

            $totalUnsubscribed = DB::table('newsletter_subscriptions')
                ->where('is_active', false)
                ->count();

            $recentSubscribers = DB::table('newsletter_subscriptions')
                ->where('is_active', true)
                ->where('subscribed_at', '>=', now()->subDays(30))
                ->count();

            $campaignStats = DB::table('newsletter_campaigns')
                ->selectRaw('
                    COUNT(*) as total_campaigns,
                    SUM(recipients_count) as total_emails_sent,
                    AVG(sent_count / recipients_count * 100) as avg_delivery_rate
                ')
                ->first();

            return [
                'total_active_subscribers' => $totalSubscribers,
                'total_unsubscribed' => $totalUnsubscribed,
                'recent_subscribers' => $recentSubscribers,
                'total_campaigns' => $campaignStats->total_campaigns ?? 0,
                'total_emails_sent' => $campaignStats->total_emails_sent ?? 0,
                'avg_delivery_rate' => round($campaignStats->avg_delivery_rate ?? 0, 2),
                'growth_rate' => $this->calculateGrowthRate()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get newsletter stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_active_subscribers' => 0,
                'total_unsubscribed' => 0,
                'recent_subscribers' => 0,
                'total_campaigns' => 0,
                'total_emails_sent' => 0,
                'avg_delivery_rate' => 0,
                'growth_rate' => 0
            ];
        }
    }

    /**
     * Get subscriber list with pagination
     */
    public function getSubscribers(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        try {
            $query = DB::table('newsletter_subscriptions')
                ->where('is_active', true);

            // Apply filters
            if (!empty($filters['email'])) {
                $query->where('email', 'like', '%' . $filters['email'] . '%');
            }

            if (!empty($filters['date_from'])) {
                $query->where('subscribed_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('subscribed_at', '<=', $filters['date_to']);
            }

            $total = $query->count();
            $subscribers = $query
                ->orderBy('subscribed_at', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return [
                'success' => true,
                'subscribers' => $subscribers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get subscribers', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve subscribers'
            ];
        }
    }

    /**
     * Send welcome email to new subscriber
     */
    protected function sendWelcomeEmail(string $email): void
    {
        try {
            $templateData = [
                'email' => $email,
                'app_name' => config('app.name'),
                'unsubscribe_url' => route('newsletter.unsubscribe', ['email' => $email])
            ];

            // Use a simple welcome template for newsletter subscribers
            $this->emailService->sendEmail(
                $email,
                '',
                'Welcome to our Newsletter!',
                'emails.newsletter-welcome',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send newsletter welcome email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate growth rate for the last 30 days
     */
    protected function calculateGrowthRate(): float
    {
        try {
            $currentMonth = DB::table('newsletter_subscriptions')
                ->where('is_active', true)
                ->where('subscribed_at', '>=', now()->subDays(30))
                ->count();

            $previousMonth = DB::table('newsletter_subscriptions')
                ->where('is_active', true)
                ->where('subscribed_at', '>=', now()->subDays(60))
                ->where('subscribed_at', '<', now()->subDays(30))
                ->count();

            if ($previousMonth == 0) {
                return $currentMonth > 0 ? 100 : 0;
            }

            return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2);

        } catch (\Exception $e) {
            Log::error('Failed to calculate growth rate', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}