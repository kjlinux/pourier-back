<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\Photographer\PhotoController as PhotographerPhotoController;
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

/*
|--------------------------------------------------------------------------
| PHASE 3: PHOTOS & CATÉGORIES
|--------------------------------------------------------------------------
*/

// Photos (Public)
Route::prefix('photos')->group(function () {
    Route::get('/', [PhotoController::class, 'index'])->name('photos.index');
    Route::get('/featured', [PhotoController::class, 'featured'])->name('photos.featured');
    Route::get('/recent', [PhotoController::class, 'recent'])->name('photos.recent');
    Route::get('/popular', [PhotoController::class, 'popular'])->name('photos.popular');
    Route::get('/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::get('/{photo}/similar', [PhotoController::class, 'similar'])->name('photos.similar');
});

// Search
Route::get('/search/photos', [SearchController::class, 'searchPhotos'])->name('search.photos');

// Categories (Public)
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/{slugOrId}', [CategoryController::class, 'show'])->name('categories.show');
});

// Photographer Routes (Protected)
Route::middleware('auth:api')->prefix('photographer')->group(function () {
    Route::prefix('photos')->group(function () {
        Route::get('/', [PhotographerPhotoController::class, 'index'])->name('photographer.photos.index');
        Route::post('/', [PhotographerPhotoController::class, 'store'])->name('photographer.photos.store');
        Route::get('/{photo}', [PhotographerPhotoController::class, 'show'])->name('photographer.photos.show');
        Route::put('/{photo}', [PhotographerPhotoController::class, 'update'])->name('photographer.photos.update');
        Route::delete('/{photo}', [PhotographerPhotoController::class, 'destroy'])->name('photographer.photos.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| PHASE 4: PANIER & COMMANDES
|--------------------------------------------------------------------------
*/

// Cart (Protected)
Route::middleware('auth:api')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/items', [CartController::class, 'addItem'])->name('cart.addItem');
    Route::put('/items/{index}', [CartController::class, 'updateItem'])->name('cart.updateItem');
    Route::delete('/items/{index}', [CartController::class, 'removeItem'])->name('cart.removeItem');
    Route::delete('/', [CartController::class, 'clear'])->name('cart.clear');
});

// Orders (Protected)
Route::middleware('auth:api')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
    Route::get('/{order}/status', [OrderController::class, 'checkStatus'])->name('orders.checkStatus');
});

/*
|--------------------------------------------------------------------------
| PHASE 5: PAIEMENTS CINETPAY
|--------------------------------------------------------------------------
*/

// Webhooks CinetPay (Public - No auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/cinetpay', [WebhookController::class, 'handleCinetPayWebhook'])->name('webhooks.cinetpay');
    Route::get('/cinetpay/return/{order}', [WebhookController::class, 'handleCinetPayReturn'])->name('webhooks.cinetpay.return');
});
