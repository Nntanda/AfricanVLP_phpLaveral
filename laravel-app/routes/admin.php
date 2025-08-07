<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\IntegrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here are the admin routes for the application. These routes require
| admin authentication and provide access to the administrative interface.
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // System Health
    Route::get('/system/health', [DashboardController::class, 'systemHealth'])->name('system.health');
    
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('users.verify-email');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::get('users/export/csv', [UserController::class, 'export'])->name('users.export');
    
    // Organization Management
    Route::resource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/toggle-status', [OrganizationController::class, 'toggleStatus'])->name('organizations.toggle-status');
    Route::get('organizations/{organization}/members', [OrganizationController::class, 'members'])->name('organizations.members');
    Route::post('organizations/{organization}/members', [OrganizationController::class, 'addMember'])->name('organizations.add-member');
    Route::delete('organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember'])->name('organizations.remove-member');
    Route::patch('organizations/{organization}/members/{user}', [OrganizationController::class, 'updateMemberRole'])->name('organizations.update-member-role');
    Route::get('organizations/export/csv', [OrganizationController::class, 'export'])->name('organizations.export');
    
    // Event Management
    Route::resource('events', EventController::class);
    Route::post('events/{event}/toggle-status', [EventController::class, 'toggleStatus'])->name('events.toggle-status');
    
    // News Management
    Route::resource('news', NewsController::class);
    Route::post('news/{news}/toggle-status', [NewsController::class, 'toggleStatus'])->name('news.toggle-status');
    Route::post('news/{news}/publish', [NewsController::class, 'publish'])->name('news.publish');
    
    // Blog Post Management
    Route::resource('blog-posts', BlogPostController::class);
    Route::post('blog-posts/{blogPost}/toggle-status', [BlogPostController::class, 'toggleStatus'])->name('blog-posts.toggle-status');
    Route::post('blog-posts/{blogPost}/publish', [BlogPostController::class, 'publish'])->name('blog-posts.publish');
    
    // Resource Management
    Route::resource('resources', ResourceController::class);
    Route::post('resources/{resource}/toggle-status', [ResourceController::class, 'toggleStatus'])->name('resources.toggle-status');
    Route::delete('resources/{resource}/files/{file}', [ResourceController::class, 'deleteFile'])->name('resources.files.delete');
    
    // Integration Management
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/', [IntegrationController::class, 'index'])->name('index');
        Route::get('/newsletter', [IntegrationController::class, 'newsletter'])->name('newsletter');
        Route::get('/health-check', [IntegrationController::class, 'healthCheck'])->name('health-check');
        
        // API endpoints for integrations
        Route::post('/test-translate', [IntegrationController::class, 'testTranslate'])->name('test-translate');
        Route::get('/supported-languages', [IntegrationController::class, 'getSupportedLanguages'])->name('supported-languages');
        Route::post('/detect-language', [IntegrationController::class, 'detectLanguage'])->name('detect-language');
        Route::get('/device-info', [IntegrationController::class, 'getDeviceInfo'])->name('device-info');
        Route::post('/test-email', [IntegrationController::class, 'testEmail'])->name('test-email');
        Route::post('/send-newsletter', [IntegrationController::class, 'sendNewsletter'])->name('send-newsletter');
        Route::get('/newsletter-subscribers', [IntegrationController::class, 'getNewsletterSubscribers'])->name('newsletter-subscribers');
        Route::post('/clear-translation-cache', [IntegrationController::class, 'clearTranslationCache'])->name('clear-translation-cache');
    });
    
    // Profile Management
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::patch('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
    
    // Settings (Super Admin only)
    Route::middleware('can:super-admin-access')->group(function () {
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::patch('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
    });
});