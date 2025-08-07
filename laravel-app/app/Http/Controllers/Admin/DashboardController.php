<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Organization;
use App\Models\Event;
use App\Models\News;
use App\Models\BlogPost;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends AdminController
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        $this->shareViewData();

        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent activities
        $recentUsers = $this->getRecentUsers();
        $recentOrganizations = $this->getRecentOrganizations();
        $recentEvents = $this->getRecentEvents();
        
        // Get chart data
        $userGrowthData = $this->getUserGrowthData();
        $organizationStats = $this->getOrganizationStats();

        return view('admin.dashboard', compact(
            'stats',
            'recentUsers',
            'recentOrganizations',
            'recentEvents',
            'userGrowthData',
            'organizationStats'
        ));
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 1)->count(),
            'verified_users' => User::where('is_email_verified', true)->count(),
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('status', 1)->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'total_news' => News::count(),
            'published_news' => News::where('status', 1)->count(),
            'total_blog_posts' => BlogPost::count(),
            'published_blog_posts' => BlogPost::where('status', 1)->count(),
            'total_resources' => Resource::count(),
            'active_resources' => Resource::where('status', 1)->count(),
        ];
    }

    /**
     * Get recent users
     */
    private function getRecentUsers($limit = 5)
    {
        return User::with(['country', 'city'])
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent organizations
     */
    private function getRecentOrganizations($limit = 5)
    {
        return Organization::with(['country', 'city', 'categoryOfOrganization'])
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent events
     */
    private function getRecentEvents($limit = 5)
    {
        return Event::with(['organization', 'country', 'city'])
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user growth data for charts
     */
    private function getUserGrowthData()
    {
        $months = [];
        $userCounts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $userCounts[] = User::whereYear('created', $date->year)
                ->whereMonth('created', $date->month)
                ->count();
        }

        return [
            'months' => $months,
            'user_counts' => $userCounts,
        ];
    }

    /**
     * Get organization statistics
     */
    private function getOrganizationStats()
    {
        return Organization::select('countries.nicename as country', DB::raw('count(*) as count'))
            ->join('countries', 'organizations.country_id', '=', 'countries.id')
            ->where('organizations.status', 1)
            ->groupBy('countries.id', 'countries.nicename')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get system health information
     */
    public function systemHealth()
    {
        $this->shareViewData();

        $health = [
            'database' => $this->checkDatabaseConnection(),
            'storage' => $this->checkStorageSpace(),
            'cache' => $this->checkCacheConnection(),
            'email' => $this->checkEmailConfiguration(),
        ];

        return view('admin.system-health', compact('health'));
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection is working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check storage space
     */
    private function checkStorageSpace()
    {
        $storagePath = storage_path();
        $freeBytes = disk_free_space($storagePath);
        $totalBytes = disk_total_space($storagePath);
        
        if ($freeBytes && $totalBytes) {
            $freeGB = round($freeBytes / 1024 / 1024 / 1024, 2);
            $totalGB = round($totalBytes / 1024 / 1024 / 1024, 2);
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
            
            $status = $usedPercent > 90 ? 'warning' : 'healthy';
            $message = "Storage: {$freeGB}GB free of {$totalGB}GB ({$usedPercent}% used)";
            
            return ['status' => $status, 'message' => $message];
        }
        
        return ['status' => 'unknown', 'message' => 'Unable to check storage space'];
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection()
    {
        try {
            cache()->put('health_check', 'test', 60);
            $value = cache()->get('health_check');
            
            if ($value === 'test') {
                return ['status' => 'healthy', 'message' => 'Cache is working'];
            } else {
                return ['status' => 'error', 'message' => 'Cache read/write failed'];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check email configuration
     */
    private function checkEmailConfiguration()
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.' . $mailer . '.host');
        
        if ($mailer && $host) {
            return ['status' => 'healthy', 'message' => "Email configured with {$mailer} ({$host})"];
        }
        
        return ['status' => 'warning', 'message' => 'Email configuration incomplete'];
    }
}