<?php

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

// Home page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Include authentication routes
require __DIR__.'/auth.php';

// Include admin routes
require __DIR__.'/admin.php';

// Include client routes
require __DIR__.'/client.php';

// Map routes
Route::prefix('map')->name('map.')->group(function () {
    Route::get('/', [App\Http\Controllers\MapController::class, 'index'])->name('index');
    Route::get('/nearby', [App\Http\Controllers\MapController::class, 'nearby'])->name('nearby');
    Route::post('/geocode', [App\Http\Controllers\MapController::class, 'geocode'])->name('geocode');
    Route::post('/reverse-geocode', [App\Http\Controllers\MapController::class, 'reverseGeocode'])->name('reverse-geocode');
    Route::get('/static-map', [App\Http\Controllers\MapController::class, 'staticMap'])->name('static-map');
    Route::get('/events-data', [App\Http\Controllers\MapController::class, 'eventsMapData'])->name('events-data');
    Route::get('/organizations-data', [App\Http\Controllers\MapController::class, 'organizationsMapData'])->name('organizations-data');
    
    // Admin routes for updating coordinates
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::patch('/events/{event}/coordinates', [App\Http\Controllers\MapController::class, 'updateEventCoordinates'])->name('events.coordinates');
        Route::patch('/organizations/{organization}/coordinates', [App\Http\Controllers\MapController::class, 'updateOrganizationCoordinates'])->name('organizations.coordinates');
    });
});