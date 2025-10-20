<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;


// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/shop-categories', [ShopCategoryController::class, 'index']);

// Product categories routes
Route::get('/product-categories', [ProductCategoryController::class, 'index']);



// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    
    Route::post('/logout', [AuthController::class, 'logout']);


    //products routes
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    


    //store
   

    Route::get('/shops', [ShopController::class, 'index']);
    Route::get('/shops/user/{userId}', [ShopController::class, 'getByUser']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::post('/shops/{companyId}/join', [ShopController::class, 'joinRequest']);
    Route::put('/shops/{id}', [ShopController::class, 'update']);
    Route::patch('/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);


    // SHop categories routes
    //Route::get('/shop-categories', [ShopCategoryController::class, 'index']);
    Route::post('/shop-categories', [ShopCategoryController::class, 'store']);

    

});
