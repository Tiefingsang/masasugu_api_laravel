<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::prefix('admin')->name('admin.')->group(function () {

    // Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Protégé
    Route::middleware(['auth', 'admin'])->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ─────────────────────────────────────────────
        // BOUTIQUES
        // ─────────────────────────────────────────────
        Route::prefix('shops')->name('shops.')->group(function () {
            Route::get('/', [ShopController::class, 'index'])->name('index');
            Route::get('/{id}', [ShopController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [ShopController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [ShopController::class, 'reject'])->name('reject');
            Route::post('/{id}/suspend', [ShopController::class, 'suspend'])->name('suspend');
            Route::post('/{id}/activate', [ShopController::class, 'activate'])->name('activate');
            Route::post('/{id}/verify-user', [ShopController::class, 'verifyUser'])->name('verify-user');
            Route::delete('/{id}', [ShopController::class, 'destroy'])->name('destroy');
        });

        // ─────────────────────────────────────────────
        // UTILISATEURS
        // ─────────────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::post('/{id}/verify', [\App\Http\Controllers\Admin\UserController::class, 'verify'])->name('verify');
            Route::post('/{id}/ban', [\App\Http\Controllers\Admin\UserController::class, 'ban'])->name('ban');
            Route::post('/{id}/unban', [\App\Http\Controllers\Admin\UserController::class, 'unban'])->name('unban');
            Route::post('/{id}/change-role', [\App\Http\Controllers\Admin\UserController::class, 'changeRole'])->name('change-role');
            Route::post('/{id}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
        });


        // ─────────────────────────────────────────────
        // PRODUITS
        // ─────────────────────────────────────────────
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\ProductController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\ProductController::class, 'reject'])->name('reject');
            Route::post('/{id}/disable', [\App\Http\Controllers\Admin\ProductController::class, 'disable'])->name('disable');
            Route::post('/{id}/enable', [\App\Http\Controllers\Admin\ProductController::class, 'enable'])->name('enable');
            Route::post('/{id}/feature', [\App\Http\Controllers\Admin\ProductController::class, 'feature'])->name('feature');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('destroy');
        });

        // ─────────────────────────────────────────────
        // COMMANDES
        // ─────────────────────────────────────────────
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('show');
            Route::post('/{id}/status', [\App\Http\Controllers\Admin\OrderController::class, 'changeStatus'])->name('change-status');
            Route::post('/{id}/confirm', [\App\Http\Controllers\Admin\OrderController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/ship', [\App\Http\Controllers\Admin\OrderController::class, 'ship'])->name('ship');
            Route::post('/{id}/deliver', [\App\Http\Controllers\Admin\OrderController::class, 'deliver'])->name('deliver');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('cancel');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'destroy'])->name('destroy');
        });


        // ─────────────────────────────────────────────
        // CATÉGORIES
        // ─────────────────────────────────────────────
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('destroy');
        });


        // ─────────────────────────────────────────────
        // NOTIFICATIONS
        // ─────────────────────────────────────────────
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
            Route::post('/send', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('send');
            Route::post('/test', [\App\Http\Controllers\Admin\NotificationController::class, 'test'])->name('test');
        });

        // ─────────────────────────────────────────────
        // STATISTIQUES
        // ─────────────────────────────────────────────
        Route::prefix('stats')->name('stats.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StatsController::class, 'index'])->name('index');
        });
    });
});
