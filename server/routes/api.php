<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AdminDatabaseController;
use App\Http\Controllers\Admin\ApiKeyController as AdminApiKeyController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\DatabaseController as UserDatabaseController;
use App\Http\Controllers\User\ProfileController as UserProfileController;
use App\Http\Controllers\External\PaymenterController;

/*
|--------------------------------------------------------------------------
| API Routes (Pure Management & External Provisioning - No Internal Billing)
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'online',
        'panel' => 'NexusDB Management API',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// 1. Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// 2. Client Management Routes
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'stats']);

    // Direct Database Management
    Route::get('/databases', [UserDatabaseController::class, 'index']);
    Route::post('/databases', [UserDatabaseController::class, 'store']); // Create database directly
    Route::get('/databases/{id}', [UserDatabaseController::class, 'show']);
    Route::post('/databases/{id}/reset-password', [UserDatabaseController::class, 'resetPassword']);
    Route::delete('/databases/{id}', [UserDatabaseController::class, 'destroy']);

    // Profile & Credentials
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::post('/change-password', [UserProfileController::class, 'changePassword']);
});

// 3. Admin Management Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/stats', [AdminDashboardController::class, 'stats']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::patch('/users/{id}/suspend', [AdminUserController::class, 'toggleSuspend']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Databases (Manage & Direct Allocate to any user)
    Route::get('/databases', [AdminDatabaseController::class, 'index']);
    Route::post('/databases', [AdminDatabaseController::class, 'store']);
    Route::get('/databases/{id}', [AdminDatabaseController::class, 'show']);
    Route::patch('/databases/{id}/suspend', [AdminDatabaseController::class, 'suspend']);
    Route::patch('/databases/{id}/unsuspend', [AdminDatabaseController::class, 'unsuspend']);
    Route::delete('/databases/{id}', [AdminDatabaseController::class, 'terminate']);

    // API Keys (For Paymenter External Provisioning)
    Route::get('/api-keys', [AdminApiKeyController::class, 'index']);
    Route::post('/api-keys', [AdminApiKeyController::class, 'store']);
    Route::patch('/api-keys/{id}/toggle', [AdminApiKeyController::class, 'toggle']);
    Route::delete('/api-keys/{id}', [AdminApiKeyController::class, 'destroy']);

    // Settings
    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::put('/settings', [AdminSettingController::class, 'update']);
});

// 4. External Paymenter Integration API
Route::prefix('external')->middleware('api.key')->group(function () {
    Route::post('/provision', [PaymenterController::class, 'provision']);
    Route::post('/suspend', [PaymenterController::class, 'suspend']);
    Route::post('/unsuspend', [PaymenterController::class, 'unsuspend']);
    Route::post('/terminate', [PaymenterController::class, 'terminate']);
    Route::get('/status/{identifier}', [PaymenterController::class, 'status']);
});
