<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ShopController;

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
    });
});
