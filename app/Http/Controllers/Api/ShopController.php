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
    // public function getByUser($userId)
    // {
    //     $shop = Company::with('user')->where('user_id', $userId)->first();

    //     if (!$shop) {
    //         return response()->json([
    //             'message' => 'Aucune boutique trouvée pour cet utilisateur',
    //             'shop' => null
    //         ], 404);
    //     }

    //     return response()->json(['shop' => $shop], 200);
    // }

    public function getByUser($userId)
    {
        $shop = Company::with('user')->where('user_id', $userId)->first();

        if (!$shop) {
            return response()->json([
                'message' => 'Aucune boutique trouvée pour cet utilisateur',
                'shop' => null
            ], 404);
        }

        // 🆕 Fallback : renvoyer contact_phone = numéro du créateur si vide
        if (empty($shop->contact_phone) && $shop->user && !empty($shop->user->phone)) {
            $shop->contact_phone = $shop->user->phone;
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

        // ✅ Logo en fichier (plus en string)
        'logo'                     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

        // 🆕 Nouveaux champs
        'is_factory'               => 'nullable|boolean',
        'banner'                   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        'certification_documents'  => 'nullable|array',
        'certification_documents.*'=> 'file|mimes:pdf,jpeg,png,jpg|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    // 🔎 Vérifier si l'utilisateur a déjà une boutique
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
        'is_factory'    => $request->is_factory ?? false,
    ]);

    // ✅ Mettre à jour l'utilisateur
    $user = \App\Models\User::find($request->user_id);
    if ($user) {
        $user->update([
            'role' => 'seller',
            'company_id' => $shop->id,
        ]);
    }

    // ✅ Upload logo
    if ($request->hasFile('logo')) {
        $shop->update([
            'logo' => $request->file('logo')->store('logos', 'public'),
        ]);
    }

    // ✅ Upload bannière
    if ($request->hasFile('banner')) {
        $shop->update([
            'banner' => $request->file('banner')->store('companies/banners', 'public'),
        ]);
    }

    // ✅ Upload documents de certification
    if ($request->hasFile('certification_documents')) {
        $docs = [];
        foreach ($request->file('certification_documents') as $file) {
            $docs[] = $file->store('companies/certifications', 'public');
        }
        $shop->update(['certification_documents' => $docs]);
    }

    return response()->json([
        'message' => 'Boutique créée avec succès 🎉',
        'shop' => $shop->fresh()->load('user'),
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

    // public function update(Request $request, $id){
    //     $shop = Company::find($id);

    //     if (!$shop) {
    //         return response()->json(['message' => 'Boutique introuvable'], 404);
    //     }

    //     $shop->update([
    //         'name' => $request->name,
    //         'description' => $request->description,
    //         'contact_phone' => $request->contactPhone,
    //         'country' => $request->country,
    //         'address' => $request->address,
    //         'website' => $request->website,
    //     ]);

    //     // gestion du logo (si envoyé)
    //     if ($request->hasFile('logo')) {
    //         $path = $request->file('logo')->store('logos', 'public');
    //         $shop->logo = asset("storage/$path");
    //         $shop->save();
    //     }

    //     return response()->json([
    //         'message' => 'Boutique mise à jour avec succès',
    //         'shop' => $shop
    //     ]);
    // }


    public function update(Request $request, $id)
{
    $shop = Company::find($id);

    if (!$shop) {
        return response()->json(['message' => 'Boutique introuvable'], 404);
    }

    // ✅ Validation
    $validated = $request->validate([
        'name'           => 'required|string|max:255',
        'description'    => 'nullable|string',
        'contact_phone'  => 'nullable|string|max:50',
        'contact_email'  => 'nullable|email|max:255',
        'country'        => 'nullable|string|max:100',
        'city'           => 'nullable|string|max:100',
        'postal_code'    => 'nullable|string|max:20',
        'address'        => 'nullable|string|max:255',
        'website'        => 'nullable|string|max:255',
        'facebook'       => 'nullable|string|max:255',
        'instagram'      => 'nullable|string|max:255',
        'tiktok'         => 'nullable|string|max:255',
        'license_number' => 'nullable|string|max:100',

        // 🆕 Nouveaux champs
        'is_factory'               => 'nullable|boolean',
        'banner'                   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        'certification_documents'  => 'nullable|array',
        'certification_documents.*'=> 'file|mimes:pdf,jpeg,png,jpg|max:10240',
    ]);

    // 🆕 Mapper camelCase → snake_case
    if ($request->has('contactPhone') && !isset($validated['contact_phone'])) {
        $validated['contact_phone'] = $request->input('contactPhone');
    }
    if ($request->has('contactEmail') && !isset($validated['contact_email'])) {
        $validated['contact_email'] = $request->input('contactEmail');
    }
    if ($request->has('licenseNumber') && !isset($validated['license_number'])) {
        $validated['license_number'] = $request->input('licenseNumber');
    }
    if ($request->has('postalCode') && !isset($validated['postal_code'])) {
        $validated['postal_code'] = $request->input('postalCode');
    }
    if ($request->has('isFactory') && !isset($validated['is_factory'])) {
        $validated['is_factory'] = $request->input('isFactory') === 'true'
            || $request->input('isFactory') === '1'
            || $request->input('isFactory') === true;
    }

    // 🆕 Gestion de la bannière
    if ($request->hasFile('banner')) {
        if ($shop->banner && !str_starts_with($shop->banner, 'http')) {
            \Storage::disk('public')->delete($shop->banner);
        }
        $validated['banner'] = $request->file('banner')->store('companies/banners', 'public');
    }

    // 🆕 Gestion des documents de certification (ajout aux existants)
    if ($request->hasFile('certification_documents')) {
        $docs = $shop->certification_documents ?? [];
        foreach ($request->file('certification_documents') as $file) {
            $docs[] = $file->store('companies/certifications', 'public');
        }
        $validated['certification_documents'] = $docs;
    }

    $shop->update($validated);

    // Gestion du logo (inchangé)
    if ($request->hasFile('logo')) {
        if ($shop->logo && !str_starts_with($shop->logo, 'http')) {
            \Storage::disk('public')->delete($shop->logo);
        }
        $path = $request->file('logo')->store('logos', 'public');
        $shop->logo = $path;
        $shop->save();
    }

    return response()->json([
        'message' => 'Boutique mise à jour avec succès',
        'shop'    => $shop->fresh()->load('user'),
    ]);
}

    // Mettre à jour le logo de la boutique
 public function updateLogo(Request $request, $id)
    {
        //  Utiliser Company au lieu de Shop (bug corrigé)
        $shop = Company::findOrFail($id);

        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo si présent
            if ($shop->logo && !str_starts_with($shop->logo, 'http')) {
                \Storage::disk('public')->delete($shop->logo);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $shop->logo = $path;   // ✅ chemin relatif (le cast $appends calcule l'URL)
            $shop->save();

            return response()->json([
                'message' => 'Logo mis à jour avec succès',
                'logo' => $shop->logo,
                'logo_url' => $shop->logo_url,
            ], 200);
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


    public function deleteCertificationDocument(Request $request, $id)
    {
        $shop = Company::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable'], 404);
        }

        $documentPath = $request->input('document_path');
        if (!$documentPath) {
            return response()->json(['message' => 'Document non spécifié'], 422);
        }

        $docs = $shop->certification_documents ?? [];

        if (($key = array_search($documentPath, $docs)) !== false) {
            \Storage::disk('public')->delete($documentPath);
            unset($docs[$key]);
            $shop->certification_documents = array_values($docs);
            $shop->save();

            return response()->json([
                'message' => 'Document supprimé',
                'shop' => $shop->fresh(),
            ]);
        }

        return response()->json(['message' => 'Document introuvable'], 404);
    }




}
