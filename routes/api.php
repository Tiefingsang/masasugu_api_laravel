<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ChatController;


// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/best-offers', [ProductController::class, 'bestOffers']);
Route::get('/products/top-rated', [ProductController::class, 'topRated']);
Route::get('/products/new', [ProductController::class, 'newProducts']);
Route::get('/produits/recents', [ProductController::class, 'recents']);


Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/shop-categories', [ShopCategoryController::class, 'index']);

Route::get('/produits/search', [ProductController::class, 'search']);



// Product categories routes
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::get('/categories', [ProductCategoryController::class, 'getCategory']);
Route::get('/categories/{id}/products', [ProductCategoryController::class, 'getProductsByCategory']);



Route::get('/products/by-shop/{company_id}', [ProductController::class, 'getProductsByShop']);

// Routes spécifiques









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

    

    

    // Video upload route
    Route::post('/products/{id}/video', [ProductController::class, 'uploadVideo']);
    //Route::get('/products/by-shop/{company_id}', [ProductController::class, 'getProductsByShop']);
    // Product images upload route
    //Route::post('/products/images', [ProductImageController::class, 'store']);

    Route::get('/products/by-shop/{id}', [ProductController::class, 'getByShop']);
    // Search products route


    


    


    //store
   

    Route::get('/shops', [ShopController::class, 'index']);
    Route::get('/shops/user/{userId}', [ShopController::class, 'getByUser']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::post('/shops/{companyId}/join', [ShopController::class, 'joinRequest']);
    Route::put('/shops/{id}', [ShopController::class, 'update']);
    Route::patch('/shops/{id}', [ShopController::class, 'update']);
    Route::delete('/shops/{id}', [ShopController::class, 'destroy']);
    //recupérer shop par produit
    Route::get('/shop-by-product/{productId}', [ShopController::class, 'getShopByProduct']);
    Route::post('/shops/{id}/update-logo', [ShopController::class, 'updateLogo']);
    // Récupérer les statistiques d'une boutique
    Route::get('/companies/{id}/stats', [ShopController::class, 'getShopStats']);
    Route::get('/companies/{id}/products/overview', [ShopController::class, 'getShopProducts']);







    // SHop categories routes
    //Route::get('/shop-categories', [ShopCategoryController::class, 'index']);
    Route::post('/shop-categories', [ShopCategoryController::class, 'store']);

    // Product images routes
    Route::get('/products/{product}/images', [ProductImageController::class, 'index']);
    Route::post('/products-images', [ProductImageController::class, 'store']);
    Route::delete('/product-images/{id}', [ProductImageController::class, 'destroy']);


    // Cart routes
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/update/{id}', [CartController::class, 'updateQuantity']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'removeItem']);
    Route::get('/cart', [CartController::class, 'getUserCart']);

    // Route to get the total count of items in the cart
    Route::get('/cart/count', [CartController::class, 'count']);


    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/user', [OrderController::class, 'index']);

    // Récupérer les commandes pour un magasin spécifique
    Route::get('/companies/{company}/orders', [OrderController::class, 'getShopOrders']);
    
    // Mettre à jour le statut d'une commande
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);



    // Chat routes
    Route::get('/conversations', [ChatController::class,'index']);
    Route::get('/conversations/{id}/messages', [ChatController::class,'messages']);
    Route::post('/messages/send', [ChatController::class,'send']);



    


    

    




    

});
