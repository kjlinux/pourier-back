<?php

use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PhotoModerationController;
use App\Http\Controllers\Api\Admin\PhotographerController as AdminPhotographerController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\Photographer\AnalyticsController as PhotographerAnalyticsController;
use App\Http\Controllers\Api\Photographer\DashboardController as PhotographerDashboardController;
use App\Http\Controllers\Api\Photographer\PhotoController as PhotographerPhotoController;
use App\Http\Controllers\Api\Photographer\RevenueController as PhotographerRevenueController;
use App\Http\Controllers\Api\Photographer\WithdrawalController as PhotographerWithdrawalController;
use App\Http\Controllers\Api\User\FavoriteController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\ProfileController;
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

/*
|--------------------------------------------------------------------------
| DOWNLOADS
|--------------------------------------------------------------------------
*/

// Downloads (Protected & Public)
Route::prefix('downloads')->group(function () {
    // Protected downloads (require authentication and purchase verification)
    Route::middleware('auth:api')->group(function () {
        Route::get('/photo/{photo}', [DownloadController::class, 'downloadPhoto'])->name('downloads.photo');
        Route::get('/order/{order}', [DownloadController::class, 'downloadOrder'])->name('downloads.order');
        Route::get('/invoice/{order}', [DownloadController::class, 'downloadInvoice'])->name('downloads.invoice');
    });

    // Public preview download (watermarked)
    Route::get('/preview/{photo}', [DownloadController::class, 'downloadPreview'])->name('downloads.preview');
});

/*
|--------------------------------------------------------------------------
| PHASE 6: PANEL ADMIN
|--------------------------------------------------------------------------
*/

// Admin Routes (Protected - requires admin role)
Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Photo Moderation
    Route::prefix('photos')->group(function () {
        Route::get('/', [PhotoModerationController::class, 'index'])->name('admin.photos.index');
        Route::get('/pending', [PhotoModerationController::class, 'pending'])->name('admin.photos.pending');
        Route::post('/{photo}/approve', [PhotoModerationController::class, 'approve'])->name('admin.photos.approve');
        Route::post('/{photo}/reject', [PhotoModerationController::class, 'reject'])->name('admin.photos.reject');
        Route::put('/{photo}/toggle-featured', [PhotoModerationController::class, 'toggleFeatured'])->name('admin.photos.toggleFeatured');
        Route::delete('/{photo}', [PhotoModerationController::class, 'destroy'])->name('admin.photos.destroy');
        Route::post('/bulk-approve', [PhotoModerationController::class, 'bulkApprove'])->name('admin.photos.bulkApprove');
        Route::post('/bulk-reject', [PhotoModerationController::class, 'bulkReject'])->name('admin.photos.bulkReject');
    });

    // Photographer Management
    Route::prefix('photographers')->group(function () {
        Route::get('/', [AdminPhotographerController::class, 'index'])->name('admin.photographers.index');
        Route::get('/pending', [AdminPhotographerController::class, 'pending'])->name('admin.photographers.pending');
        Route::get('/{photographer}', [AdminPhotographerController::class, 'show'])->name('admin.photographers.show');
        Route::post('/{photographer}/approve', [AdminPhotographerController::class, 'approve'])->name('admin.photographers.approve');
        Route::post('/{photographer}/reject', [AdminPhotographerController::class, 'reject'])->name('admin.photographers.reject');
        Route::put('/{photographer}/suspend', [AdminPhotographerController::class, 'suspend'])->name('admin.photographers.suspend');
        Route::put('/{photographer}/activate', [AdminPhotographerController::class, 'activate'])->name('admin.photographers.activate');
    });

    // Withdrawal Management
    Route::prefix('withdrawals')->group(function () {
        Route::get('/', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawals.index');
        Route::get('/pending', [AdminWithdrawalController::class, 'pending'])->name('admin.withdrawals.pending');
        Route::get('/{withdrawal}', [AdminWithdrawalController::class, 'show'])->name('admin.withdrawals.show');
        Route::post('/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('admin.withdrawals.approve');
        Route::post('/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('admin.withdrawals.reject');
        Route::post('/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete'])->name('admin.withdrawals.complete');
    });

    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
        Route::put('/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');
        Route::put('/{user}/activate', [AdminUserController::class, 'activate'])->name('admin.users.activate');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/revenue', [AdminAnalyticsController::class, 'revenue'])->name('admin.analytics.revenue');
        Route::get('/sales', [AdminAnalyticsController::class, 'sales'])->name('admin.analytics.sales');
        Route::get('/photographers', [AdminAnalyticsController::class, 'photographers'])->name('admin.analytics.photographers');
        Route::get('/user-growth', [AdminAnalyticsController::class, 'userGrowth'])->name('admin.analytics.userGrowth');
    });
});

/*
|--------------------------------------------------------------------------
| PHASE 7: PANEL PHOTOGRAPHE AVANCÉ
|--------------------------------------------------------------------------
*/

// Extended Photographer Routes (Protected - requires photographer role)
Route::middleware(['auth:api', 'photographer'])->prefix('photographer')->group(function () {
    // Dashboard
    Route::get('/dashboard', [PhotographerDashboardController::class, 'index'])->name('photographer.dashboard');
    Route::get('/stats', [PhotographerDashboardController::class, 'stats'])->name('photographer.stats');

    // Revenue Management
    Route::prefix('revenue')->group(function () {
        Route::get('/', [PhotographerRevenueController::class, 'index'])->name('photographer.revenue.index');
        Route::get('/available', [PhotographerRevenueController::class, 'available'])->name('photographer.revenue.available');
        Route::get('/pending', [PhotographerRevenueController::class, 'pending'])->name('photographer.revenue.pending');
        Route::get('/history', [PhotographerRevenueController::class, 'history'])->name('photographer.revenue.history');
    });

    // Withdrawal Management
    Route::prefix('withdrawals')->group(function () {
        Route::get('/', [PhotographerWithdrawalController::class, 'index'])->name('photographer.withdrawals.index');
        Route::post('/', [PhotographerWithdrawalController::class, 'store'])->name('photographer.withdrawals.store');
        Route::get('/{withdrawal}', [PhotographerWithdrawalController::class, 'show'])->name('photographer.withdrawals.show');
        Route::delete('/{withdrawal}', [PhotographerWithdrawalController::class, 'destroy'])->name('photographer.withdrawals.destroy');
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/sales', [PhotographerAnalyticsController::class, 'sales'])->name('photographer.analytics.sales');
        Route::get('/popular-photos', [PhotographerAnalyticsController::class, 'popularPhotos'])->name('photographer.analytics.popularPhotos');
    });
});

/*
|--------------------------------------------------------------------------
| PHASE 8: FEATURES UTILISATEUR
|--------------------------------------------------------------------------
*/

// User Routes (Protected - requires authentication)
Route::middleware('auth:api')->prefix('user')->group(function () {
    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('user.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
    Route::put('/avatar', [ProfileController::class, 'updateAvatar'])->name('user.profile.updateAvatar');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('user.profile.updatePassword');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('user.notifications.index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('user.notifications.unread');
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('user.notifications.markAsRead');
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('user.notifications.markAllAsRead');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('user.notifications.destroy');
    });

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index'])->name('user.favorites.index');
        Route::post('/{photo}', [FavoriteController::class, 'store'])->name('user.favorites.store');
        Route::delete('/{photo}', [FavoriteController::class, 'destroy'])->name('user.favorites.destroy');
    });
});
