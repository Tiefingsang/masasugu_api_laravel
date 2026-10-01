<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\StatsController;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::prefix('admin')->name('admin.')->group(function () {

    // ═══════════════════════════════════════════════
    // AUTHENTIFICATION
    // ═══════════════════════════════════════════════
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // ═══════════════════════════════════════════════
    // ROUTES PROTÉGÉES (auth + admin)
    // ═══════════════════════════════════════════════
    Route::middleware(['auth', 'admin'])->group(function () {

        // ─── DASHBOARD ───
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // ─── BOUTIQUES ───
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

        // ─── UTILISATEURS ───
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{id}', [UserController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UserController::class, 'update'])->name('update');
            Route::post('/{id}/verify', [UserController::class, 'verify'])->name('verify');
            Route::post('/{id}/ban', [UserController::class, 'ban'])->name('ban');
            Route::post('/{id}/unban', [UserController::class, 'unban'])->name('unban');
            Route::post('/{id}/change-role', [UserController::class, 'changeRole'])->name('change-role');
            Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
        });

        // ─── PRODUITS ───
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/{id}', [ProductController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [ProductController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [ProductController::class, 'reject'])->name('reject');
            Route::post('/{id}/disable', [ProductController::class, 'disable'])->name('disable');
            Route::post('/{id}/enable', [ProductController::class, 'enable'])->name('enable');
            Route::post('/{id}/feature', [ProductController::class, 'feature'])->name('feature');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
        });

        // ─── COMMANDES ───
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{id}', [OrderController::class, 'show'])->name('show');
            Route::post('/{id}/status', [OrderController::class, 'changeStatus'])->name('change-status');
            Route::post('/{id}/confirm', [OrderController::class, 'confirm'])->name('confirm');
            Route::post('/{id}/ship', [OrderController::class, 'ship'])->name('ship');
            Route::post('/{id}/deliver', [OrderController::class, 'deliver'])->name('deliver');
            Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
            Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');
        });

        // ─── CATÉGORIES ───
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // ─── NOTIFICATIONS ───
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('/send', [NotificationController::class, 'send'])->name('send');
            Route::post('/test', [NotificationController::class, 'test'])->name('test');
        });

        // ─── STATISTIQUES ───
        Route::prefix('stats')->name('stats.')->group(function () {
            Route::get('/', [StatsController::class, 'index'])->name('index');
        });
    });
});
