<?php

namespace App\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Organization::class => OrganizationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates
        Gate::define('admin-access', function (User $user) {
            return in_array($user->role, ['admin', 'super_admin']) && $user->status === 1;
        });

        Gate::define('super-admin-access', function (User $user) {
            return $user->role === 'super_admin' && $user->status === 1;
        });

        Gate::define('client-access', function (User $user) {
            return $user->status === 1 && $user->is_email_verified;
        });

        Gate::define('organization-owner', function (User $user, Organization $organization) {
            return $organization->users()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'owner')
                ->wherePivot('status', 1)
                ->exists();
        });

        Gate::define('organization-admin', function (User $user, Organization $organization) {
            return $organization->users()
                ->where('user_id', $user->id)
                ->wherePivot('role', ['admin', 'owner'])
                ->wherePivot('status', 1)
                ->exists();
        });

        Gate::define('organization-moderator', function (User $user, Organization $organization) {
            return $organization->users()
                ->where('user_id', $user->id)
                ->wherePivot('role', ['moderator', 'admin', 'owner'])
                ->wherePivot('status', 1)
                ->exists();
        });

        Gate::define('organization-member', function (User $user, Organization $organization) {
            return $organization->users()
                ->where('user_id', $user->id)
                ->wherePivot('status', 1)
                ->exists();
        });
    }
}