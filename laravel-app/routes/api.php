<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// File upload routes
Route::middleware(['auth'])->group(function () {
    // Resource file management
    Route::post('/resources/{resource}/files', [FileUploadController::class, 'uploadResourceFiles'])
        ->name('api.resources.files.upload');
    
    Route::get('/resources/{resource}/files', [FileUploadController::class, 'getResourceFiles'])
        ->name('api.resources.files.index');
    
    Route::put('/resource-files/{resourceFile}', [FileUploadController::class, 'updateResourceFile'])
        ->name('api.resource-files.update');
    
    Route::delete('/resource-files/{resourceFile}', [FileUploadController::class, 'deleteResourceFile'])
        ->name('api.resource-files.delete');
    
    Route::get('/resource-files/{resourceFile}/download', [FileUploadController::class, 'downloadResourceFile'])
        ->name('api.resource-files.download');
});