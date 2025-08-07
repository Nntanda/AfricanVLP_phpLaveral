<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organization;

class OrganizationAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = 'member'): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $organizationId = $request->route('organization') ?? $request->route('id');

        if (!$organizationId) {
            abort(404, 'Organization not found.');
        }

        $organization = Organization::find($organizationId);

        if (!$organization) {
            abort(404, 'Organization not found.');
        }

        // Check if user is a member of the organization
        $membership = $user->organizations()->where('organization_id', $organizationId)->first();

        if (!$membership) {
            abort(403, 'You do not have access to this organization.');
        }

        // Check permission level
        $userRole = $membership->pivot->role;
        $membershipStatus = $membership->pivot->status;

        // Check if membership is active
        if ($membershipStatus !== 1) {
            abort(403, 'Your membership in this organization is not active.');
        }

        // Define role hierarchy
        $roleHierarchy = [
            'member' => 1,
            'moderator' => 2,
            'admin' => 3,
            'owner' => 4,
        ];

        $requiredLevel = $roleHierarchy[$permission] ?? 1;
        $userLevel = $roleHierarchy[$userRole] ?? 0;

        if ($userLevel < $requiredLevel) {
            abort(403, 'You do not have sufficient permissions for this action.');
        }

        // Add organization to request for easy access in controllers
        $request->merge(['current_organization' => $organization]);

        return $next($request);
    }
}