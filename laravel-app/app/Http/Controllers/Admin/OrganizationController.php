<?php

namespace App\Http\Controllers\Admin;

use App\Models\Organization;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Models\CategoryOfOrganization;
use App\Models\InstitutionType;
use App\Services\RoleService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrganizationController extends AdminController
{
    /**
     * Display a listing of organizations
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = Organization::with(['country', 'city', 'categoryOfOrganization', 'institutionType', 'creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('about', 'like', "%{$search}%")
                  ->orWhere('website', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by institution type
        if ($request->filled('institution_type_id')) {
            $query->where('institution_type_id', $request->institution_type_id);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $organizations = $query->paginate(20)->withQueryString();

        // Get filter options
        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $categories = CategoryOfOrganization::where('status', 1)->orderBy('name')->get();
        $institutionTypes = InstitutionType::where('status', 1)->orderBy('name')->get();

        return view('admin.organizations.index', compact(
            'organizations', 
            'countries', 
            'categories', 
            'institutionTypes'
        ));
    }

    /**
     * Show the form for creating a new organization
     */
    public function create()
    {
        $this->shareViewData();

        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $categories = CategoryOfOrganization::where('status', 1)->orderBy('name')->get();
        $institutionTypes = InstitutionType::where('status', 1)->orderBy('name')->get();

        return view('admin.organizations.create', compact('countries', 'categories', 'institutionTypes'));
    }

    /**
     * Store a newly created organization
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'about' => 'nullable|string',
            'type' => 'nullable|string|max:45',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'category_id' => 'nullable|exists:category_of_organizations,id',
            'institution_type_id' => 'nullable|exists:institution_types,id',
            'date_of_establishment' => 'nullable|date',
            'phone_number' => 'nullable|string|max:16',
            'website' => 'nullable|string|max:55',
            'facebbok_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'twitter_url' => 'nullable|string|max:255',
            'government_affliliation' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $organization = Organization::create([
                'name' => $request->name,
                'about' => $request->about,
                'type' => $request->type,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'category_id' => $request->category_id,
                'institution_type_id' => $request->institution_type_id,
                'date_of_establishment' => $request->date_of_establishment,
                'phone_number' => $request->phone_number,
                'website' => $request->website,
                'facebbok_url' => $request->facebbok_url,
                'instagram_url' => $request->instagram_url,
                'twitter_url' => $request->twitter_url,
                'government_affliliation' => $request->government_affliliation,
                'user_id' => $this->getAuthUser()->id,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.organizations.show', $organization)
                ->with('success', 'Organization created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create organization. Please try again.'])->withInput();
        }
    }

    /**
     * Display the specified organization
     */
    public function show(Organization $organization)
    {
        $this->shareViewData();

        $organization->load([
            'country', 
            'city', 
            'categoryOfOrganization', 
            'institutionType', 
            'creator',
            'users' => function($query) {
                $query->withPivot('role', 'status')->orderBy('organization_users.created', 'desc');
            },
            'events' => function($query) {
                $query->orderBy('created', 'desc')->limit(5);
            },
            'news' => function($query) {
                $query->orderBy('created', 'desc')->limit(5);
            },
            'resources' => function($query) {
                $query->orderBy('created', 'desc')->limit(5);
            }
        ]);

        // Get member statistics
        $memberStats = [
            'total_members' => $organization->users()->count(),
            'active_members' => $organization->users()->wherePivot('status', 1)->count(),
            'owners' => $organization->users()->wherePivot('role', 'owner')->count(),
            'admins' => $organization->users()->wherePivot('role', 'admin')->count(),
            'moderators' => $organization->users()->wherePivot('role', 'moderator')->count(),
            'members' => $organization->users()->wherePivot('role', 'member')->count(),
        ];

        return view('admin.organizations.show', compact('organization', 'memberStats'));
    }

    /**
     * Show the form for editing the specified organization
     */
    public function edit(Organization $organization)
    {
        $this->shareViewData();

        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $cities = $organization->country_id ? 
            City::where('country_id', $organization->country_id)->orderBy('name')->get() : 
            collect();
        $categories = CategoryOfOrganization::where('status', 1)->orderBy('name')->get();
        $institutionTypes = InstitutionType::where('status', 1)->orderBy('name')->get();

        return view('admin.organizations.edit', compact(
            'organization', 
            'countries', 
            'cities', 
            'categories', 
            'institutionTypes'
        ));
    }

    /**
     * Update the specified organization
     */
    public function update(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'about' => 'nullable|string',
            'type' => 'nullable|string|max:45',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'category_id' => 'nullable|exists:category_of_organizations,id',
            'institution_type_id' => 'nullable|exists:institution_types,id',
            'date_of_establishment' => 'nullable|date',
            'phone_number' => 'nullable|string|max:16',
            'website' => 'nullable|string|max:55',
            'facebbok_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'twitter_url' => 'nullable|string|max:255',
            'government_affliliation' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $organization->update([
                'name' => $request->name,
                'about' => $request->about,
                'type' => $request->type,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'category_id' => $request->category_id,
                'institution_type_id' => $request->institution_type_id,
                'date_of_establishment' => $request->date_of_establishment,
                'phone_number' => $request->phone_number,
                'website' => $request->website,
                'facebbok_url' => $request->facebbok_url,
                'instagram_url' => $request->instagram_url,
                'twitter_url' => $request->twitter_url,
                'government_affliliation' => $request->government_affliliation,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.organizations.show', $organization)
                ->with('success', 'Organization updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update organization. Please try again.'])->withInput();
        }
    }

    /**
     * Remove the specified organization
     */
    public function destroy(Organization $organization)
    {
        try {
            // Check if organization has active members
            $activeMemberCount = $organization->users()->wherePivot('status', 1)->count();
            
            if ($activeMemberCount > 0) {
                return back()->withErrors(['error' => 'Cannot delete organization with active members. Please remove all members first.']);
            }

            $organization->delete();
            return redirect()->route('admin.organizations.index')
                ->with('success', 'Organization deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete organization. Please try again.']);
        }
    }

    /**
     * Toggle organization status
     */
    public function toggleStatus(Organization $organization)
    {
        try {
            $organization->update(['status' => $organization->status === 1 ? 0 : 1]);
            
            $status = $organization->status === 1 ? 'activated' : 'deactivated';
            return back()->with('success', "Organization {$status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update organization status.']);
        }
    }

    /**
     * Show organization members
     */
    public function members(Organization $organization)
    {
        $this->shareViewData();

        $members = $organization->users()
            ->withPivot('role', 'status', 'created', 'modified')
            ->with(['country', 'city'])
            ->orderBy('organization_users.created', 'desc')
            ->paginate(20);

        $availableUsers = User::where('status', 1)
            ->where('is_email_verified', true)
            ->whereNotIn('id', $organization->users()->pluck('users.id'))
            ->orderBy('first_name')
            ->get();

        $roles = RoleService::ORGANIZATION_ROLES;

        return view('admin.organizations.members', compact('organization', 'members', 'availableUsers', 'roles'));
    }

    /**
     * Add member to organization
     */
    public function addMember(Request $request, Organization $organization)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => ['required', Rule::in(array_keys(RoleService::ORGANIZATION_ROLES))],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Check if user is already a member
            if ($organization->users()->where('user_id', $request->user_id)->exists()) {
                return back()->withErrors(['user_id' => 'User is already a member of this organization.']);
            }

            $organization->users()->attach($request->user_id, [
                'role' => $request->role,
                'status' => 1,
                'created' => now(),
                'modified' => now(),
            ]);

            return back()->with('success', 'Member added successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to add member. Please try again.']);
        }
    }

    /**
     * Remove member from organization
     */
    public function removeMember(Organization $organization, User $user)
    {
        try {
            // Check if user is the only owner
            $membership = $organization->users()->where('user_id', $user->id)->first();
            
            if ($membership && $membership->pivot->role === 'owner') {
                $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
                if ($ownerCount <= 1) {
                    return back()->withErrors(['error' => 'Cannot remove the last owner of the organization.']);
                }
            }

            $organization->users()->detach($user->id);
            return back()->with('success', 'Member removed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to remove member. Please try again.']);
        }
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Request $request, Organization $organization, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', Rule::in(array_keys(RoleService::ORGANIZATION_ROLES))],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Check if changing the last owner
            $currentMembership = $organization->users()->where('user_id', $user->id)->first();
            
            if ($currentMembership && $currentMembership->pivot->role === 'owner' && $request->role !== 'owner') {
                $ownerCount = $organization->users()->wherePivot('role', 'owner')->count();
                if ($ownerCount <= 1) {
                    return back()->withErrors(['error' => 'Cannot change role of the last owner. Please assign another owner first.']);
                }
            }

            $organization->users()->updateExistingPivot($user->id, [
                'role' => $request->role,
                'modified' => now(),
            ]);

            return back()->with('success', 'Member role updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update member role. Please try again.']);
        }
    }

    /**
     * Export organizations to CSV
     */
    public function export(Request $request, ExportService $exportService)
    {
        $this->shareViewData();

        $query = Organization::with(['country', 'city', 'category']);

        // Apply same filters as index method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('about', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_verified')) {
            if ($request->is_verified === '1') {
                $query->where('is_verified', true);
            } else {
                $query->where(function ($q) {
                    $q->where('is_verified', false)->orWhereNull('is_verified');
                });
            }
        }

        $organizations = $query->orderBy('created', 'desc')->get();

        $filename = 'organizations_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return $exportService->exportOrganizations($organizations, $filename);
    }
}