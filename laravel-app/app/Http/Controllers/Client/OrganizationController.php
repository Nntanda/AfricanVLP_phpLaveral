<?php

namespace App\Http\Controllers\Client;

use App\Models\Organization;
use App\Models\Country;
use App\Models\CategoryOfOrganization;
use App\Services\RoleService;
use Illuminate\Http\Request;

class OrganizationController extends ClientController
{
    /**
     * Display a listing of organizations
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = Organization::with(['country', 'city', 'categoryOfOrganization'])
            ->where('status', 1)
            ->orderBy('name', 'asc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('about', 'like', "%{$search}%");
            });
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $organizations = $query->paginate(12)->withQueryString();

        // Get filter options
        $countries = Country::where('status', 1)
            ->whereHas('organizations', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('nicename')
            ->get();

        $categories = CategoryOfOrganization::where('status', 1)
            ->whereHas('organizations', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        return view('client.organizations.index', compact('organizations', 'countries', 'categories'));
    }

    /**
     * Display the specified organization
     */
    public function show(Organization $organization)
    {
        $this->shareViewData();

        // Check if organization is active
        if ($organization->status !== 1) {
            abort(404);
        }

        $organization->load([
            'country', 
            'city', 
            'categoryOfOrganization', 
            'institutionType',
            'users' => function($query) {
                $query->wherePivot('status', 1)->withPivot('role');
            },
            'events' => function($query) {
                $query->where('status', 1)->orderBy('start_date', 'desc')->limit(5);
            },
            'news' => function($query) {
                $query->where('status', 1)->orderBy('created', 'desc')->limit(5);
            },
            'resources' => function($query) {
                $query->where('status', 1)->orderBy('created', 'desc')->limit(5);
            }
        ]);

        // Check if current user is a member
        $user = $this->getAuthUser();
        $isMember = $organization->users()->where('user_id', $user->id)->wherePivot('status', 1)->exists();
        $userRole = null;
        
        if ($isMember) {
            $membership = $organization->users()->where('user_id', $user->id)->first();
            $userRole = $membership ? $membership->pivot->role : null;
        }

        return view('client.organizations.show', compact('organization', 'isMember', 'userRole'));
    }

    /**
     * Show user's organizations
     */
    public function myOrganizations()
    {
        $this->shareViewData();

        $user = $this->getAuthUser();
        
        $organizations = $user->organizations()
            ->wherePivot('status', 1)
            ->withPivot('role', 'created')
            ->with(['country', 'city', 'categoryOfOrganization'])
            ->orderBy('organization_users.created', 'desc')
            ->paginate(12);

        return view('client.organizations.my', compact('organizations'));
    }

    /**
     * Join an organization
     */
    public function join(Organization $organization)
    {
        $user = $this->getAuthUser();

        // Check if organization is active
        if ($organization->status !== 1) {
            return back()->withErrors(['error' => 'This organization is not available for joining.']);
        }

        // Check if user is already a member
        if ($organization->users()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['error' => 'You are already a member of this organization.']);
        }

        try {
            $organization->users()->attach($user->id, [
                'role' => 'member',
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]);

            return back()->with('success', 'Successfully joined the organization!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to join organization. Please try again.']);
        }
    }

    /**
     * Leave an organization
     */
    public function leave(Organization $organization)
    {
        $user = $this->getAuthUser();

        // Check if user is a member
        $membership = $organization->users()->where('user_id', $user->id)->first();
        
        if (!$membership) {
            return back()->withErrors(['error' => 'You are not a member of this organization.']);
        }

        // Check if user is the only owner
        if ($membership->pivot->role === 'owner') {
            $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return back()->withErrors(['error' => 'You cannot leave as you are the only owner of this organization.']);
            }
        }

        try {
            $organization->users()->detach($user->id);
            return back()->with('success', 'Successfully left the organization.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to leave organization. Please try again.']);
        }
    }
}