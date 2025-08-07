<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrganizationInvitationService
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send invitation to join organization
     */
    public function sendInvitation(
        Organization $organization,
        string $email,
        string $role,
        User $invitedBy,
        string $message = null
    ): array {
        try {
            // Check if user is already a member
            $existingUser = User::where('email', $email)->first();
            if ($existingUser && $organization->users()->where('user_id', $existingUser->id)->exists()) {
                return [
                    'success' => false,
                    'message' => 'User is already a member of this organization'
                ];
            }

            // Check if there's already a pending invitation
            $existingInvitation = OrganizationInvitation::where('organization_id', $organization->id)
                ->where('email', $email)
                ->pending()
                ->first();

            if ($existingInvitation) {
                return [
                    'success' => false,
                    'message' => 'There is already a pending invitation for this email'
                ];
            }

            DB::beginTransaction();

            // Create invitation
            $invitation = OrganizationInvitation::create([
                'organization_id' => $organization->id,
                'invited_by_user_id' => $invitedBy->id,
                'email' => $email,
                'role' => $role,
                'message' => $message,
                'expires_at' => now()->addDays(7)
            ]);

            // Send invitation email
            $emailSent = $this->emailService->sendOrganizationInviteEmail(
                $email,
                $organization,
                $role,
                $invitedBy
            );

            if (!$emailSent) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Failed to send invitation email'
                ];
            }

            DB::commit();

            Log::info('Organization invitation sent', [
                'organization_id' => $organization->id,
                'email' => $email,
                'role' => $role,
                'invited_by' => $invitedBy->id
            ]);

            return [
                'success' => true,
                'message' => 'Invitation sent successfully',
                'invitation' => $invitation
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send organization invitation', [
                'organization_id' => $organization->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send invitation'
            ];
        }
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(string $token, User $user = null): array
    {
        try {
            $invitation = OrganizationInvitation::where('token', $token)->first();

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invalid invitation token'
                ];
            }

            if (!$invitation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'This invitation is no longer valid'
                ];
            }

            // If user is not provided, try to find by email
            if (!$user) {
                $user = User::where('email', $invitation->email)->first();
                
                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'Please create an account first to accept this invitation',
                        'redirect_to_register' => true,
                        'email' => $invitation->email
                    ];
                }
            }

            // Verify email matches
            if ($user->email !== $invitation->email) {
                return [
                    'success' => false,
                    'message' => 'This invitation is for a different email address'
                ];
            }

            DB::beginTransaction();

            // Check if user is already a member
            if ($invitation->organization->users()->where('user_id', $user->id)->exists()) {
                $invitation->markAsExpired();
                DB::commit();
                
                return [
                    'success' => false,
                    'message' => 'You are already a member of this organization'
                ];
            }

            // Add user to organization
            $invitation->organization->users()->attach($user->id, [
                'role' => $invitation->role,
                'status' => 1, // Active
                'created' => now(),
                'modified' => now()
            ]);

            // Accept invitation
            $invitation->accept();

            DB::commit();

            Log::info('Organization invitation accepted', [
                'invitation_id' => $invitation->id,
                'user_id' => $user->id,
                'organization_id' => $invitation->organization_id
            ]);

            return [
                'success' => true,
                'message' => 'Invitation accepted successfully',
                'organization' => $invitation->organization,
                'role' => $invitation->role
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accept organization invitation', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to accept invitation'
            ];
        }
    }

    /**
     * Reject invitation
     */
    public function rejectInvitation(string $token): array
    {
        try {
            $invitation = OrganizationInvitation::where('token', $token)->first();

            if (!$invitation) {
                return [
                    'success' => false,
                    'message' => 'Invalid invitation token'
                ];
            }

            if (!$invitation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'This invitation is no longer valid'
                ];
            }

            $invitation->reject();

            Log::info('Organization invitation rejected', [
                'invitation_id' => $invitation->id,
                'organization_id' => $invitation->organization_id
            ]);

            return [
                'success' => true,
                'message' => 'Invitation rejected successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reject organization invitation', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject invitation'
            ];
        }
    }

    /**
     * Cancel invitation
     */
    public function cancelInvitation(OrganizationInvitation $invitation, User $user): array
    {
        try {
            // Check if user has permission to cancel
            if (!$this->canManageInvitation($invitation->organization, $user)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to cancel this invitation'
                ];
            }

            if (!$invitation->isPending()) {
                return [
                    'success' => false,
                    'message' => 'This invitation cannot be cancelled'
                ];
            }

            $invitation->markAsExpired();

            Log::info('Organization invitation cancelled', [
                'invitation_id' => $invitation->id,
                'cancelled_by' => $user->id
            ]);

            return [
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to cancel organization invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel invitation'
            ];
        }
    }

    /**
     * Get invitations for organization
     */
    public function getOrganizationInvitations(Organization $organization, array $filters = []): array
    {
        try {
            $query = $organization->invitations()->with(['invitedBy']);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['email'])) {
                $query->where('email', 'like', '%' . $filters['email'] . '%');
            }

            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            $invitations = $query->orderBy('created', 'desc')->get();

            return [
                'success' => true,
                'invitations' => $invitations
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get organization invitations', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve invitations'
            ];
        }
    }

    /**
     * Get invitations for user email
     */
    public function getUserInvitations(string $email): array
    {
        try {
            $invitations = OrganizationInvitation::where('email', $email)
                ->with(['organization', 'invitedBy'])
                ->orderBy('created', 'desc')
                ->get();

            return [
                'success' => true,
                'invitations' => $invitations
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get user invitations', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve invitations'
            ];
        }
    }

    /**
     * Clean up expired invitations
     */
    public function cleanupExpiredInvitations(): int
    {
        try {
            $expiredCount = OrganizationInvitation::expired()->count();
            
            OrganizationInvitation::expired()->update(['status' => 'expired']);

            Log::info('Cleaned up expired invitations', [
                'count' => $expiredCount
            ]);

            return $expiredCount;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired invitations', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Check if user can manage invitations for organization
     */
    protected function canManageInvitation(Organization $organization, User $user): bool
    {
        // Super admin and admin can manage all invitations
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return true;
        }

        // Check if user is organization admin or moderator
        $membership = $organization->users()->where('user_id', $user->id)->first();
        
        return $membership && in_array($membership->pivot->role, ['admin', 'moderator']);
    }

    /**
     * Get invitation statistics
     */
    public function getInvitationStats(Organization $organization = null): array
    {
        try {
            $query = OrganizationInvitation::query();
            
            if ($organization) {
                $query->where('organization_id', $organization->id);
            }

            $stats = [
                'total' => $query->count(),
                'pending' => $query->where('status', 'pending')->count(),
                'accepted' => $query->where('status', 'accepted')->count(),
                'rejected' => $query->where('status', 'rejected')->count(),
                'expired' => $query->where('status', 'expired')->count(),
            ];

            $stats['acceptance_rate'] = $stats['total'] > 0 
                ? round(($stats['accepted'] / $stats['total']) * 100, 2) 
                : 0;

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get invitation statistics', [
                'organization_id' => $organization?->id,
                'error' => $e->getMessage()
            ]);

            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
                'expired' => 0,
                'acceptance_rate' => 0
            ];
        }
    }
}