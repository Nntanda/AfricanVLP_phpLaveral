<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Services\RoleService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends AdminController
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = User::with(['country', 'city']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by email verification
        if ($request->filled('email_verified')) {
            $query->where('is_email_verified', $request->email_verified === '1');
        }

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate(20)->withQueryString();

        // Get filter options
        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $roles = RoleService::USER_ROLES;

        return view('admin.users.index', compact('users', 'countries', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $this->shareViewData();

        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $roles = RoleService::USER_ROLES;

        return view('admin.users.create', compact('countries', 'roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'nullable|string|max:16',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'role' => ['required', Rule::in(array_keys(RoleService::USER_ROLES))],
            'status' => 'required|integer|in:0,1',
            'is_email_verified' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if current user can assign the selected role
        if (!$this->canAssignRole($request->role)) {
            return back()->withErrors(['role' => 'You do not have permission to assign this role.'])->withInput();
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'role' => $request->role,
                'status' => $request->status,
                'is_email_verified' => $request->boolean('is_email_verified'),
                'registration_status' => 1,
            ]);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create user. Please try again.'])->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $this->shareViewData();

        $user->load(['country', 'city', 'organizations']);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $this->shareViewData();

        $countries = Country::where('status', 1)->orderBy('nicename')->get();
        $cities = $user->country_id ? City::where('country_id', $user->country_id)->orderBy('name')->get() : collect();
        $roles = RoleService::USER_ROLES;

        return view('admin.users.edit', compact('user', 'countries', 'cities', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'phone_number' => 'nullable|string|max:16',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'role' => ['required', Rule::in(array_keys(RoleService::USER_ROLES))],
            'status' => 'required|integer|in:0,1',
            'is_email_verified' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if current user can assign the selected role
        if (!$this->canAssignRole($request->role)) {
            return back()->withErrors(['role' => 'You do not have permission to assign this role.'])->withInput();
        }

        try {
            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'role' => $request->role,
                'status' => $request->status,
                'is_email_verified' => $request->boolean('is_email_verified'),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update user. Please try again.'])->withInput();
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deletion of super admin users by non-super admin
        if ($user->role === 'super_admin' && !$this->isSuperAdmin()) {
            return back()->withErrors(['error' => 'You cannot delete super admin users.']);
        }

        // Prevent users from deleting themselves
        if ($user->id === $this->getAuthUser()->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        try {
            $user->delete();
            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete user. Please try again.']);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        try {
            $user->update(['status' => $user->status === 1 ? 0 : 1]);
            
            $status = $user->status === 1 ? 'activated' : 'deactivated';
            return back()->with('success', "User {$status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update user status.']);
        }
    }

    /**
     * Verify user email
     */
    public function verifyEmail(User $user)
    {
        try {
            $user->update([
                'is_email_verified' => true,
                'email_verification_token' => null,
            ]);

            return back()->with('success', 'User email verified successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to verify user email.']);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
                'password_reset_token' => null,
                'password_reset_expires' => null,
            ]);

            return back()->with('success', 'User password reset successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reset user password.']);
        }
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request, ExportService $exportService)
    {
        $this->shareViewData();

        $query = User::with(['country', 'city']);

        // Apply same filters as index method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->filled('email_verified')) {
            if ($request->email_verified === '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $users = $query->orderBy('created', 'desc')->get();

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return $exportService->exportUsers($users, $filename);
    }

    /**
     * Check if current user can assign a specific role
     */
    private function canAssignRole(string $role): bool
    {
        $currentUser = $this->getAuthUser();

        // Super admin can assign any role
        if ($currentUser->role === 'super_admin') {
            return true;
        }

        // Admin can assign user role only
        if ($currentUser->role === 'admin') {
            return $role === 'user';
        }

        return false;
    }
}