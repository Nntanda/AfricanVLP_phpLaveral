<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->roleService = $roleService;
    }

    /**
     * Get the authenticated admin user
     */
    protected function getAuthUser()
    {
        return Auth::user();
    }

    /**
     * Check if current user is super admin
     */
    protected function isSuperAdmin()
    {
        return $this->roleService->isSuperAdmin($this->getAuthUser());
    }

    /**
     * Share common data with all admin views
     */
    protected function shareViewData()
    {
        $user = $this->getAuthUser();
        
        view()->share([
            'authUser' => $user,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'userRole' => $user->role,
        ]);
    }
}