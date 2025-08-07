<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view organizations
    }

    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        // Public organizations can be viewed by anyone
        if ($organization->status === 1) {
            return true;
        }

        // Private organizations require membership
        return $this->isMember($user, $organization);
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        return $user->status === 1 && $user->is_email_verified;
    }

    /**
     * Determine whether the user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['admin', 'owner']);
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['owner']) || $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can manage members of the organization.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['admin', 'owner']);
    }

    /**
     * Determine whether the user can manage events for the organization.
     */
    public function manageEvents(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['moderator', 'admin', 'owner']);
    }

    /**
     * Determine whether the user can manage news for the organization.
     */
    public function manageNews(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['moderator', 'admin', 'owner']);
    }

    /**
     * Determine whether the user can manage resources for the organization.
     */
    public function manageResources(User $user, Organization $organization): bool
    {
        return $this->hasRole($user, $organization, ['moderator', 'admin', 'owner']);
    }

    /**
     * Determine whether the user can join the organization.
     */
    public function join(User $user, Organization $organization): bool
    {
        return !$this->isMember($user, $organization) && $user->status === 1;
    }

    /**
     * Determine whether the user can leave the organization.
     */
    public function leave(User $user, Organization $organization): bool
    {
        $membership = $this->getMembership($user, $organization);
        
        // Owner cannot leave unless there's another owner
        if ($membership && $membership->pivot->role === 'owner') {
            $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
            return $ownerCount > 1;
        }

        return $this->isMember($user, $organization);
    }

    /**
     * Check if user is a member of the organization
     */
    private function isMember(User $user, Organization $organization): bool
    {
        return $organization->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 1)
            ->exists();
    }

    /**
     * Check if user has specific role(s) in the organization
     */
    private function hasRole(User $user, Organization $organization, array $roles): bool
    {
        $membership = $this->getMembership($user, $organization);
        
        if (!$membership || $membership->pivot->status !== 1) {
            return false;
        }

        return in_array($membership->pivot->role, $roles);
    }

    /**
     * Get user's membership in the organization
     */
    private function getMembership(User $user, Organization $organization)
    {
        return $organization->users()
            ->where('user_id', $user->id)
            ->first();
    }
}