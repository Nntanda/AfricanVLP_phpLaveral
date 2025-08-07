<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->middleware('auth');
        $this->middleware('client');
        $this->roleService = $roleService;
    }

    /**
     * Get the authenticated client user
     */
    protected function getAuthUser()
    {
        return Auth::user();
    }

    /**
     * Check if current user can access client interface
     */
    protected function canAccessClient()
    {
        return $this->roleService->canAccessClient($this->getAuthUser());
    }

    /**
     * Share common data with all client views
     */
    protected function shareViewData()
    {
        $user = $this->getAuthUser();
        
        view()->share([
            'authUser' => $user,
            'userRole' => $user->role,
            'userOrganizations' => $user->organizations()->wherePivot('status', 1)->get(),
        ]);
    }
}