<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organization;

class RoleService
{
    /**
     * Available user roles
     */
    const USER_ROLES = [
        'user' => 'Regular User',
        'admin' => 'Administrator',
        'super_admin' => 'Super Administrator',
    ];

    /**
     * Available organization roles
     */
    const ORGANIZATION_ROLES = [
        'member' => 'Member',
        'moderator' => 'Moderator',
        'admin' => 'Administrator',
        'owner' => 'Owner',
    ];

    /**
     * Role hierarchy levels
     */
    const ROLE_HIERARCHY = [
        'member' => 1,
        'moderator' => 2,
        'admin' => 3,
        'owner' => 4,
    ];

    /**
     * Check if user has admin access
     */
    public function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']) && $user->status === 1;
    }

    /**
     * Check if user has super admin access
     */
    public function isSuperAdmin(User $user): bool
    {
        return $user->role === 'super_admin' && $user->status === 1;
    }

    /**
     * Check if user can access client interface
     */
    public function canAccessClient(User $user): bool
    {
        return $user->status === 1 && $user->is_email_verified;
    }

    /**
     * Get user's role in organization
     */
    public function getUserOrganizationRole(User $user, Organization $organization): ?string
    {
        $membership = $organization->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 1)
            ->first();

        return $membership ? $membership->pivot->role : null;
    }

    /**
     * Check if user has minimum role in organization
     */
    public function hasMinimumOrganizationRole(User $user, Organization $organization, string $minimumRole): bool
    {
        $userRole = $this->getUserOrganizationRole($user, $organization);
        
        if (!$userRole) {
            return false;
        }

        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? 0;
        $minimumLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 0;

        return $userLevel >= $minimumLevel;
    }

    /**
     * Assign role to user in organization
     */
    public function assignOrganizationRole(User $user, Organization $organization, string $role): bool
    {
        if (!array_key_exists($role, self::ORGANIZATION_ROLES)) {
            return false;
        }

        $organization->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]
        ]);

        return true;
    }

    /**
     * Remove user from organization
     */
    public function removeFromOrganization(User $user, Organization $organization): bool
    {
        // Check if user is the only owner
        if ($this->getUserOrganizationRole($user, $organization) === 'owner') {
            $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return false; // Cannot remove the last owner
            }
        }

        $organization->users()->detach($user->id);
        return true;
    }

    /**
     * Get organization permissions for user
     */
    public function getOrganizationPermissions(User $user, Organization $organization): array
    {
        $role = $this->getUserOrganizationRole($user, $organization);
        
        if (!$role) {
            return [];
        }

        $permissions = [
            'view' => true,
            'comment' => true,
        ];

        if ($this->hasMinimumOrganizationRole($user, $organization, 'moderator')) {
            $permissions = array_merge($permissions, [
                'create_events' => true,
                'create_news' => true,
                'create_resources' => true,
                'moderate_content' => true,
            ]);
        }

        if ($this->hasMinimumOrganizationRole($user, $organization, 'admin')) {
            $permissions = array_merge($permissions, [
                'manage_members' => true,
                'edit_organization' => true,
                'manage_roles' => true,
            ]);
        }

        if ($this->hasMinimumOrganizationRole($user, $organization, 'owner')) {
            $permissions = array_merge($permissions, [
                'delete_organization' => true,
                'transfer_ownership' => true,
            ]);
        }

        return $permissions;
    }

    /**
     * Get available roles for user assignment
     */
    public function getAvailableRoles(User $assigningUser, Organization $organization): array
    {
        $assigningUserRole = $this->getUserOrganizationRole($assigningUser, $organization);
        
        if (!$assigningUserRole) {
            return [];
        }

        $assigningUserLevel = self::ROLE_HIERARCHY[$assigningUserRole] ?? 0;
        $availableRoles = [];

        foreach (self::ORGANIZATION_ROLES as $role => $label) {
            $roleLevel = self::ROLE_HIERARCHY[$role] ?? 0;
            
            // Users can only assign roles lower than their own
            if ($roleLevel < $assigningUserLevel) {
                $availableRoles[$role] = $label;
            }
        }

        return $availableRoles;
    }
}