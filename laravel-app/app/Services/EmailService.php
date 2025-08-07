<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;

class EmailService
{
    protected $sendGridApiKey;
    protected $fromEmail;
    protected $fromName;
    protected $isConfigured = false;

    public function __construct()
    {
        $this->sendGridApiKey = config('services.sendgrid.api_key');
        $this->fromEmail = config('mail.from.address');
        $this->fromName = config('mail.from.name');
        $this->isConfigured = !empty($this->sendGridApiKey);
    }

    /**
     * Check if SendGrid is configured
     */
    public function isAvailable(): bool
    {
        return $this->isConfigured;
    }
    /**
     * Send email verification email
     */
    public function sendVerificationEmail(User $user): bool
    {
        try {
            $verificationUrl = route('verification.verify', ['token' => $user->email_verification_token]);
            
            $templateData = [
                'user' => $user,
                'verification_url' => $verificationUrl,
                'app_name' => config('app.name'),
                'app_url' => config('app.url')
            ];

            return $this->sendEmail(
                $user->email,
                $user->first_name . ' ' . $user->last_name,
                'Verify Your Email Address - AU VLP',
                'emails.verification',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user, string $token): bool
    {
        try {
            $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
            
            $templateData = [
                'user' => $user,
                'reset_url' => $resetUrl,
                'app_name' => config('app.name'),
                'app_url' => config('app.url')
            ];

            return $this->sendEmail(
                $user->email,
                $user->first_name . ' ' . $user->last_name,
                'Reset Your Password - AU VLP',
                'emails.password-reset',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send welcome email after registration
     */
    public function sendWelcomeEmail(User $user): bool
    {
        try {
            $templateData = [
                'user' => $user,
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                'dashboard_url' => route('client.dashboard')
            ];

            return $this->sendEmail(
                $user->email,
                $user->first_name . ' ' . $user->last_name,
                'Welcome to AU VLP!',
                'emails.welcome',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send organization invitation email
     */
    public function sendOrganizationInviteEmail(string $email, Organization $organization, string $role, User $invitedBy): bool
    {
        try {
            $inviteUrl = route('organizations.invite.accept', ['token' => 'placeholder_token']);
            
            $templateData = [
                'email' => $email,
                'organization' => $organization,
                'role' => $role,
                'invited_by' => $invitedBy,
                'invite_url' => $inviteUrl,
                'app_name' => config('app.name'),
                'app_url' => config('app.url')
            ];

            return $this->sendEmail(
                $email,
                '',
                "Invitation to join {$organization->name} - AU VLP",
                'emails.organization-invite',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send organization invite email', [
                'email' => $email,
                'organization_id' => $organization->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send newsletter email
     */
    public function sendNewsletterEmail(array $recipients, string $subject, string $content, array $templateData = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';

            $data = array_merge($templateData, [
                'recipient_email' => $email,
                'recipient_name' => $name,
                'content' => $content
            ]);

            if ($this->sendEmail($email, $name, $subject, 'emails.newsletter', $data)) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to send to {$email}";
            }
        }

        return $results;
    }

    /**
     * Send notification email
     */
    public function sendNotificationEmail(User $user, string $subject, string $message, array $additionalData = []): bool
    {
        try {
            $templateData = array_merge([
                'user' => $user,
                'subject' => $subject,
                'message' => $message,
                'app_name' => config('app.name'),
                'app_url' => config('app.url')
            ], $additionalData);

            return $this->sendEmail(
                $user->email,
                $user->first_name . ' ' . $user->last_name,
                $subject,
                'emails.notification',
                $templateData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send notification email', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Core method to send email via SendGrid API
     */
    protected function sendEmail(string $toEmail, string $toName, string $subject, string $template, array $templateData): bool
    {
        try {
            // Render the email template
            $htmlContent = View::make($template, $templateData)->render();
            $textContent = strip_tags($htmlContent);

            if ($this->isAvailable()) {
                // Send via SendGrid API
                return $this->sendViaSendGrid($toEmail, $toName, $subject, $htmlContent, $textContent);
            } else {
                // Fallback to Laravel's mail system
                return $this->sendViaLaravel($toEmail, $toName, $subject, $htmlContent, $templateData);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $toEmail,
                'subject' => $subject,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send email via SendGrid API
     */
    protected function sendViaSendGrid(string $toEmail, string $toName, string $subject, string $htmlContent, string $textContent): bool
    {
        try {
            $payload = [
                'personalizations' => [
                    [
                        'to' => [
                            [
                                'email' => $toEmail,
                                'name' => $toName
                            ]
                        ],
                        'subject' => $subject
                    ]
                ],
                'from' => [
                    'email' => $this->fromEmail,
                    'name' => $this->fromName
                ],
                'content' => [
                    [
                        'type' => 'text/plain',
                        'value' => $textContent
                    ],
                    [
                        'type' => 'text/html',
                        'value' => $htmlContent
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->sendGridApiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.sendgrid.com/v3/mail/send', $payload);

            if ($response->successful()) {
                Log::info('Email sent successfully via SendGrid', [
                    'to' => $toEmail,
                    'subject' => $subject
                ]);
                return true;
            } else {
                Log::error('SendGrid API error', [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('SendGrid email sending failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Fallback to Laravel's mail system
     */
    protected function sendViaLaravel(string $toEmail, string $toName, string $subject, string $htmlContent, array $templateData): bool
    {
        try {
            Mail::send([], [], function ($message) use ($toEmail, $toName, $subject, $htmlContent) {
                $message->to($toEmail, $toName)
                        ->subject($subject)
                        ->html($htmlContent);
            });

            Log::info('Email sent successfully via Laravel Mail', [
                'to' => $toEmail,
                'subject' => $subject
            ]);
            return true;

        } catch (\Exception $e) {
            Log::error('Laravel mail sending failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get email sending statistics
     */
    public function getEmailStats(): array
    {
        // This would typically query a database table that tracks email sends
        // For now, return placeholder data
        return [
            'total_sent' => 0,
            'total_delivered' => 0,
            'total_bounced' => 0,
            'total_opened' => 0,
            'total_clicked' => 0,
            'delivery_rate' => 0,
            'open_rate' => 0,
            'click_rate' => 0
        ];
    }
}