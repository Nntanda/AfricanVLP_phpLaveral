<?php

use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\NewsController;
use App\Http\Controllers\Client\EventController;
use App\Http\Controllers\Client\OrganizationController;
use App\Http\Controllers\Client\ResourceController;
use App\Http\Controllers\Client\VolunteerController;
use App\Http\Controllers\Client\ForumController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\MessageController;
use App\Http\Controllers\Client\NotificationController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Email Verification Routes
Route::get('/email/verify', [AuthController::class, 'showVerifyEmailForm'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->name('verification.send');

// Public Content Routes
Route::get('/news', [NewsController::class, 'publicIndex'])->name('news.public');
Route::get('/news/{news}', [NewsController::class, 'publicShow'])->name('news.public.show');
Route::get('/events', [EventController::class, 'publicIndex'])->name('events.public');
Route::get('/events/{event}', [EventController::class, 'publicShow'])->name('events.public.show');

// Protected Client Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/personalized-content', [DashboardController::class, 'getPersonalizedContent'])->name('dashboard.personalized');
    Route::get('/dashboard/activity-summary', [DashboardController::class, 'getActivitySummary'])->name('dashboard.activity');
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/upload-image', [ProfileController::class, 'uploadImage'])->name('upload-image');
    });
    
    // News Routes
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('/{news}', [NewsController::class, 'show'])->name('show');
        Route::get('/tagged/{tag}', [NewsController::class, 'tagged'])->name('tagged');
    });
    
    // Events Routes
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::post('/{event}/register', [EventController::class, 'register'])->name('register');
        Route::delete('/{event}/unregister', [EventController::class, 'unregister'])->name('unregister');
    });
    
    // Resources Routes
    Route::prefix('resources')->name('resources.')->group(function () {
        Route::get('/', [ResourceController::class, 'index'])->name('index');
        Route::get('/{resource}', [ResourceController::class, 'show'])->name('show');
        Route::get('/{resource}/download', [ResourceController::class, 'download'])->name('download');
    });
    
    // Volunteering Routes
    Route::prefix('volunteer')->name('volunteer.')->group(function () {
        Route::get('/', [VolunteerController::class, 'index'])->name('index');
        Route::get('/opportunities', [VolunteerController::class, 'opportunities'])->name('opportunities');
        Route::get('/opportunities/{opportunity}', [VolunteerController::class, 'showOpportunity'])->name('opportunities.show');
        Route::post('/opportunities/{opportunity}/apply', [VolunteerController::class, 'applyForOpportunity'])->name('opportunities.apply');
        Route::post('/opportunities/{opportunity}/withdraw', [VolunteerController::class, 'withdrawApplication'])->name('opportunities.withdraw');
        Route::get('/my-applications', [VolunteerController::class, 'myApplications'])->name('my-applications');
        Route::get('/history', [VolunteerController::class, 'history'])->name('history');
        Route::get('/interests', [VolunteerController::class, 'interests'])->name('interests');
        Route::post('/interests', [VolunteerController::class, 'updateInterests'])->name('interests.update');
    });
    
    // Messages Routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::get('/search', [MessageController::class, 'search'])->name('search');
        Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('/{conversation}/reply', [MessageController::class, 'storeMessage'])->name('reply');
        Route::post('/{conversation}/archive', [MessageController::class, 'archive'])->name('archive');
        Route::post('/{conversation}/unarchive', [MessageController::class, 'unarchive'])->name('unarchive');
    });

    // Notifications Routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/read/all', [NotificationController::class, 'deleteRead'])->name('delete-read');
    });
    
});

// Organization-specific Routes (require organization membership)
Route::middleware(['auth', 'verified'])->prefix('organizations/{organization}')->name('organizations.')->group(function () {
    
    // Organization Dashboard
    Route::get('/', [OrganizationController::class, 'dashboard'])->name('dashboard');
    Route::get('/members', [OrganizationController::class, 'members'])->name('members');
    Route::get('/alumni', [OrganizationController::class, 'alumni'])->name('alumni');
    Route::get('/events', [OrganizationController::class, 'events'])->name('events');
    Route::get('/news', [OrganizationController::class, 'news'])->name('news');
    
    // Forum Routes
    Route::prefix('forum')->name('forum.')->group(function () {
        Route::get('/', [ForumController::class, 'index'])->name('index');
        Route::get('/threads/create', [ForumController::class, 'createThread'])->name('threads.create');
        Route::post('/threads', [ForumController::class, 'storeThread'])->name('threads.store');
        Route::get('/threads/{thread}', [ForumController::class, 'showThread'])->name('threads.show');
        Route::post('/threads/{thread}/posts', [ForumController::class, 'storePost'])->name('posts.store');
        Route::put('/posts/{post}', [ForumController::class, 'updatePost'])->name('posts.update');
        Route::delete('/posts/{post}', [ForumController::class, 'deletePost'])->name('posts.delete');
    });
    
    // Organization Management (for organization admins)
    Route::middleware(['organization.admin'])->group(function () {
        Route::get('/manage', [OrganizationController::class, 'manage'])->name('manage');
        Route::put('/update', [OrganizationController::class, 'update'])->name('update');
        Route::post('/invite-user', [OrganizationController::class, 'inviteUser'])->name('invite-user');
        Route::post('/remove-user/{user}', [OrganizationController::class, 'removeUser'])->name('remove-user');
        Route::post('/update-user-role/{user}', [OrganizationController::class, 'updateUserRole'])->name('update-user-role');
        Route::post('/add-to-alumni/{user}', [OrganizationController::class, 'addToAlumni'])->name('add-to-alumni');
    });
    
});

// API Routes for AJAX calls
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/countries/{country}/cities', function($countryId) {
        return \App\Models\City::where('country_id', $countryId)->get();
    })->name('cities.by-country');
    
    Route::get('/organizations/search', [OrganizationController::class, 'search'])->name('organizations.search');
    Route::get('/volunteering-categories', function() {
        return \App\Models\VolunteeringCategory::active()->ordered()->get();
    })->name('volunteering-categories');
    
    // Messages and Notifications API
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    
    // Volunteer API
    Route::get('/volunteer/stats', [VolunteerController::class, 'getStats'])->name('volunteer.stats');
});

// Language switching route
Route::get('/choose-language', function() {
    return view('language-selector');
})->name('language.choose');

Route::post('/set-language/{locale}', function($locale) {
    if (in_array($locale, ['en', 'fr', 'ar', 'pt', 'es'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('language.set');