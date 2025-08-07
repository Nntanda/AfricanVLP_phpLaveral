<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can view any user
        return in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can update any user (except super_admin role changes)
        if (in_array($user->role, ['admin', 'super_admin'])) {
            // Only super_admin can change super_admin roles
            if ($model->role === 'super_admin' && $user->role !== 'super_admin') {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only super_admin can delete users
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Only admins can change roles
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return false;
        }

        // Only super_admin can assign super_admin role
        if ($model->role === 'super_admin' && $user->role !== 'super_admin') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage organization memberships.
     */
    public function manageOrganizations(User $user, User $model): bool
    {
        // Users can manage their own organization memberships
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can manage any user's organization memberships
        return in_array($user->role, ['admin', 'super_admin']);
    }
}