<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 

use App\Models\Company;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * 🏪 Créer une nouvelle boutique (Company)
     */
    /* public function store(Request $request)
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

        // Vérifier si l’utilisateur a déjà une boutique
        $existingShop = Company::where('user_id', $request->user_id)->first();
        if ($existingShop) {
            return response()->json([
                'message' => 'Cet utilisateur possède déjà une boutique.',
                'shop' => $existingShop
            ], 409);
        }

        // Créer la boutique
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

        return response()->json([
            'message' => 'Boutique créée avec succès 🎉',
            'shop' => $shop
        ], 201);
    } */
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

    // Supprimer une boutique
    public function destroy($id){
        $shop = Company::find($id);
        if (!$shop) {
            return response()->json(['message' => 'Boutique introuvable'], 404);
        }
        $shop->delete();
        return response()->json(['message' => 'Boutique supprimée avec succès']);;
    }

}
