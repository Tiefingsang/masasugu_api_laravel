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
use Illuminate\Http\Request;


// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/best-offers', [ProductController::class, 'bestOffers']);
Route::get('/products/top-rated', [ProductController::class, 'topRated']);
Route::get('/products/new', [ProductController::class, 'newProducts']);
Route::get('/produits/recents', [ProductController::class, 'recents']);
Route::post('/products/{id}/rate', [ProductController::class, 'rateProduct']);
Route::post('/products/search-by-keywords', [ProductController::class, 'searchByKeywords']);



Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/shop-categories', [ShopCategoryController::class, 'index']);

Route::get('/produits/search', [ProductController::class, 'search']);
Route::post('/search-by-image', [ProductController::class, 'searchByImage']);



// Product categories routes
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::get('/categories', [ProductCategoryController::class, 'getCategory']);
Route::get('/categories/{id}/products', [ProductCategoryController::class, 'getProductsByCategory']);



Route::get('/products/by-shop/{company_id}', [ProductController::class, 'getProductsByShop']);


// ═══════════════════════════════════════════════
// WEBHOOKS (publics)
// ═══════════════════════════════════════════════
Route::post('/webhooks/{gateway}', [\App\Http\Controllers\Api\WebhookController::class, 'handle']);
Route::get('/payments/{gateway}/return', [\App\Http\Controllers\Api\WebhookController::class, 'return']);
Route::get('/payments/{gateway}/cancel', [\App\Http\Controllers\Api\WebhookController::class, 'cancel']);









// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/user/fcm-token', [AuthController::class, 'saveFcmToken']);


    //products routes
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::post('/products/{id}/like', [ProductController::class, 'toggleLike']);
    Route::post('/products/{id}/rate', [ProductController::class, 'rateProduct']);





    // Video upload route
    Route::post('/products/{id}/video', [ProductController::class, 'uploadVideo']);
    //Route::get('/products/by-shop/{company_id}', [ProductController::class, 'getProductsByShop']);
    // Product images upload route
    //Route::post('/products/images', [ProductImageController::class, 'store']);

    Route::get('/products/by-shop/{id}', [ProductController::class, 'getByShop']);
    // Search products route



    Route::get('/notifications', function () {
        return auth()->user()->notifications;
    });

    Route::get('/notifications/unread', function () {
        return auth()->user()->unreadNotifications;
    });

    Route::post('/notifications/mark-as-read', function (Request $request) {
        $request->validate(['id' => 'required|string']);
        $notification = auth()->user()->notifications()->where('id', $request->id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
    });

    Route::post('/notifications/mark-all-as-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });








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
    Route::post('/conversations', [ChatController::class,'createOrGetConversation']); //
    Route::get('/conversations/{id}/messages', [ChatController::class,'messages']);
    Route::post('/messages', [ChatController::class,'send']);
    Route::post('/messages/upload', [ChatController::class, 'upload']);
    Route::post('/conversations/{id}/mark-as-read', [ChatController::class, 'markAsRead']);




    // ═══════════════════════════════════════════════
    // WEBHOOKS (publics — appelés par les gateways)
    // ═══════════════════════════════════════════════
    Route::post('/webhooks/{gateway}', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
        ->name('webhooks.handle');

    Route::get('/payments/{gateway}/return', [\App\Http\Controllers\Api\WebhookController::class, 'return'])
        ->name('payments.return');

    Route::get('/payments/{gateway}/cancel', [\App\Http\Controllers\Api\WebhookController::class, 'cancel'])
        ->name('payments.cancel');


        // ═══════════════════════════════════════════════
    // PAYMENTS — Endpoints authentifiés
    // ═══════════════════════════════════════════════


    // Pays supportés
    Route::get('/countries', [\App\Http\Controllers\Api\CountryController::class, 'index']);

    // Moyens de paiement par pays
    Route::get('/payment-methods', [\App\Http\Controllers\Api\PaymentController::class, 'methods']);

    // Initier un paiement
    Route::post('/payments/initiate', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);

    // Vérifier le statut
    Route::get('/payments/{transactionId}/status', [\App\Http\Controllers\Api\PaymentController::class, 'status']);

    // ═══ WALLET ═══
    Route::get('/wallet/balance', [\App\Http\Controllers\Api\WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [\App\Http\Controllers\Api\WalletController::class, 'transactions']);
    Route::get('/wallet/stats', [\App\Http\Controllers\Api\WalletController::class, 'stats']);

    // ═══ PAYOUT METHODS ═══
    Route::get('/payout-methods', [\App\Http\Controllers\Api\PayoutController::class, 'indexMethods']);
    Route::post('/payout-methods', [\App\Http\Controllers\Api\PayoutController::class, 'storeMethod']);
    Route::delete('/payout-methods/{id}', [\App\Http\Controllers\Api\PayoutController::class, 'destroyMethod']);

    // ═══ PAYOUTS ═══
    Route::post('/payouts/request', [\App\Http\Controllers\Api\PayoutController::class, 'requestPayout']);
    Route::get('/payouts', [\App\Http\Controllers\Api\PayoutController::class, 'index']);



    

    // 🎯 Interactions / Recommandations
    Route::post('/interactions', [App\Http\Controllers\Api\InteractionController::class, 'store']);
    Route::post('/interactions/batch', [App\Http\Controllers\Api\InteractionController::class, 'storeBatch']);
    Route::get('/interactions/preferences', [App\Http\Controllers\Api\InteractionController::class, 'myPreferences']);
    Route::post('/interactions/tracking', [App\Http\Controllers\Api\InteractionController::class, 'toggleTracking']);















});
