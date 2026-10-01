<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Company;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    /**
     * 🔍 Récupère la boutique d’un utilisateur
     */
    public function getByUser($userId)
    {
        $shop = Company::with('user')->where('user_id', $userId)->first();

        if (!$shop) {
            return response()->json([
                'message' => 'Aucune boutique trouvée pour cet utilisateur',
                'shop' => null
            ], 404);
        }

        return response()->json(['shop' => $shop], 200);
    }

    
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id'       => 'required|integer|exists:users,id',
        'name'          => 'required|string|max:255',
        'category_id'   => 'nullable|integer|exists:categories,id',
        'contact_email' => 'nullable|email|max:255',
        'contact_phone' => 'nullable|string|max:50',
        'country'       => 'nullable|string|max:100',
        'address'       => 'nullable|string|max:255',
        'website'       => 'nullable|string|max:255',
        'logo'          => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    // 🔎 Vérifier si l’utilisateur a déjà une boutique
    $existingShop = Company::where('user_id', $request->user_id)->first();
    if ($existingShop) {
        return response()->json([
            'message' => 'Cet utilisateur possède déjà une boutique.',
            'shop' => $existingShop
        ], 409);
    }

    // 🏪 Créer la boutique
    $shop = Company::create([
        'user_id'       => $request->user_id,
        'name'          => $request->name,
        'country'       => $request->country,
        'address'       => $request->address,
        'website'       => $request->website,
        'is_verified'   => false,
        'contact_email' => $request->contact_email,
        'contact_phone' => $request->contact_phone,
        'logo'          => $request->logo,
    ]);

    // ✅ Mettre à jour l'utilisateur
    $user = \App\Models\User::find($request->user_id);

    if ($user) {
        $user->update([
            'role' => 'seller',
            'company_id' => $shop->id, // relie le user à sa boutique
        ]);
    }

    return response()->json([
        'message' => 'Boutique créée avec succès 🎉',
        'shop' => $shop,
        'user' => $user,
    ], 201);
}


    /**
     * 🤝 Envoyer une demande pour rejoindre une boutique existante
     */
    public function joinRequest(Request $request, $companyId)
    {
        $company = Company::find($companyId);

        if (!$company) {
            return response()->json(['message' => 'Boutique introuvable'], 404);
        }

        // Tu pourras plus tard ajouter ici la logique :
        // - créer une table "company_requests"
        // - ou "company_user" pour gérer les employés d’une boutique

        return response()->json([
            'message' => 'Demande envoyée avec succès à la boutique ' . $company->name,
        ], 200);
    }

    /**
     * 📋 Liste de toutes les boutiques
     */
    public function index()
    {
        $shops = Company::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json(['shops' => $shops], 200);
    }

    public function update(Request $request, $id){
        $shop = Company::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable'], 404);
        }

        $shop->update([
            'name' => $request->name,
            'description' => $request->description,
            'contact_phone' => $request->contactPhone,
            'country' => $request->country,
            'address' => $request->address,
            'website' => $request->website,
        ]);

        // gestion du logo (si envoyé)
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $shop->logo = asset("storage/$path");
            $shop->save();
        }

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'shop' => $shop
        ]);
    }

    // Mettre à jour le logo de la boutique
    public function updateLogo(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('shops/logos', 'public');
            $shop->logo = '/storage/' . $path;
            $shop->save();

            return response()->json(['message' => 'Logo mis à jour avec succès', 'logo' => $shop->logo], 200);
        }

        return response()->json(['message' => 'Aucun fichier reçu'], 400);
    }


    // Supprimer une boutique
    public function destroy($id){
        $shop = Company::find($id);
        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable'], 404);
        }
        $shop->delete();
        return response()->json(['message' => 'Boutique supprimée avec succès']);;
    }

    // Récupérer la boutique par produit companyId
    public function getShopByProduct($productId)
    {
        $product = \App\Models\Product::with('company')->find($productId);

        if (!$product) {
            return response()->json(['message' => 'Produit introuvable'], 404);
        }

        $shop = $product->company;

        if ($shop) {
            // Ajoute l’URL complète du logo
            $shop->logo_url = $shop->logo ? asset('storage/' . $shop->logo) : null;
        }

        return response()->json(['shop' => $shop], 200);
    }

    // Récupérer les statistiques d’une boutique


    public function getShopStats($companyId){
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Nombre de produits appartenant à cette entreprise
        $productsCount = Product::where('company_id', $companyId)->count();

        // Commandes associées à cette entreprise
        $orders = Order::whereHas('items.product', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();

        // Nombre de commandes
        $ordersCount = $orders->count();

        // Somme totale des ventes
        $totalSales = $orders->sum('total');

        // Nombre de clients uniques
        $clientsCount = $orders->pluck('user_id')->unique()->count();

        return response()->json([
            'products' => $productsCount,
            'orders' => $ordersCount,
            'sales' => $totalSales,
            'clients' => $clientsCount,
        ]);
    }

    // Récupérer les produits récents et les best-sellers d’une boutique


    public function getShopProducts($shopId){
        $recentProducts = Product::where('company_id', $shopId)
            ->latest()
            ->take(5)
            ->get();

        $bestSellers = Product::where('company_id', $shopId)
            ->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'recent' => $recentProducts,
            'best_sellers' => $bestSellers,
        ]);
    }




}
