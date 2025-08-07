<?php

namespace App\Http\Controllers\Client;

use App\Models\Event;
use App\Models\News;
use App\Models\BlogPost;
use App\Models\Organization;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends ClientController
{
    /**
     * Display the client dashboard
     */
    public function index()
    {
        $this->shareViewData();

        $user = $this->getAuthUser();

        // Get user's organizations
        $userOrganizations = $user->organizations()->wherePivot('status', 1)->get();
        $organizationIds = $userOrganizations->pluck('id')->toArray();

        // Get dashboard data
        $upcomingEvents = $this->getUpcomingEvents($organizationIds);
        $recentNews = $this->getRecentNews($organizationIds);
        $recentBlogPosts = $this->getRecentBlogPosts();
        $recentResources = $this->getRecentResources($organizationIds);
        
        // Get user statistics
        $userStats = $this->getUserStats($user);
        
        // Get recommended content
        $recommendedOrganizations = $this->getRecommendedOrganizations($user);

        return view('client.dashboard', compact(
            'upcomingEvents',
            'recentNews',
            'recentBlogPosts',
            'recentResources',
            'userStats',
            'recommendedOrganizations'
        ));
    }

    /**
     * Get upcoming events
     */
    private function getUpcomingEvents($organizationIds, $limit = 5)
    {
        $query = Event::with(['organization', 'country', 'city'])
            ->where('status', 1)
            ->where('start_date', '>', now())
            ->orderBy('start_date', 'asc');

        // If user has organizations, prioritize their events
        if (!empty($organizationIds)) {
            $query->where(function ($q) use ($organizationIds) {
                $q->whereIn('organization_id', $organizationIds)
                  ->orWhereNull('organization_id'); // Include public events
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get recent news
     */
    private function getRecentNews($organizationIds, $limit = 5)
    {
        $query = News::with(['organization', 'region'])
            ->where('status', 1)
            ->orderBy('created', 'desc');

        // If user has organizations, prioritize their news
        if (!empty($organizationIds)) {
            $query->where(function ($q) use ($organizationIds) {
                $q->whereIn('organization_id', $organizationIds)
                  ->orWhereNull('organization_id'); // Include general news
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get recent blog posts
     */
    private function getRecentBlogPosts($limit = 5)
    {
        return BlogPost::with(['author'])
            ->where('status', 1)
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent resources
     */
    private function getRecentResources($organizationIds, $limit = 5)
    {
        $query = Resource::with(['organization'])
            ->where('status', 1)
            ->orderBy('created', 'desc');

        // If user has organizations, prioritize their resources
        if (!empty($organizationIds)) {
            $query->where(function ($q) use ($organizationIds) {
                $q->whereIn('organization_id', $organizationIds)
                  ->orWhereNull('organization_id'); // Include public resources
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get user statistics
     */
    private function getUserStats($user)
    {
        return [
            'organizations_count' => $user->organizations()->wherePivot('status', 1)->count(),
            'events_participated' => 0, // Will implement when we have participation tracking
            'resources_accessed' => 0, // Will implement when we have access tracking
            'profile_completion' => $this->calculateProfileCompletion($user),
        ];
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user)
    {
        $fields = [
            'first_name', 'last_name', 'email', 'phone_number', 
            'date_of_birth', 'gender', 'country_id', 'city_id', 'about'
        ];
        
        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }
        
        return round(($completedFields / count($fields)) * 100);
    }

    /**
     * Get recommended organizations
     */
    private function getRecommendedOrganizations($user, $limit = 3)
    {
        $userOrganizationIds = $user->organizations()->pluck('organizations.id')->toArray();
        
        return Organization::where('status', 1)
            ->whereNotIn('id', $userOrganizationIds)
            ->when($user->country_id, function ($query) use ($user) {
                $query->where('country_id', $user->country_id);
            })
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Show user profile
     */
    public function profile()
    {
        $this->shareViewData();

        $user = $this->getAuthUser();
        $user->load(['country', 'city', 'organizations' => function($query) {
            $query->wherePivot('status', 1)->withPivot('role', 'created');
        }]);

        $profileCompletion = $this->calculateProfileCompletion($user);

        return view('client.profile', compact('user', 'profileCompletion'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $this->getAuthUser();

        $request->validate([
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'phone_number' => 'nullable|string|max:16',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'about' => 'nullable|string',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        try {
            $user->update($request->only([
                'first_name', 'last_name', 'phone_number', 'date_of_birth',
                'gender', 'about', 'country_id', 'city_id'
            ]));

            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
        }
    }
}