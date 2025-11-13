<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

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

// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

// Protected Auth Routes (requires JWT authentication)
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
});

// Test route to verify API is working
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Pourier API is running!',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');
