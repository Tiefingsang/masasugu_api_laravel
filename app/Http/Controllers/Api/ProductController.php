<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Afficher tous les produits (liste publique ou filtrée)
     */
    public function index(Request $request)
    {
        $query = Product::with(['company', 'user', 'images', 'category'])
            ->where('status', 'approved');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Créer un produit (par un vendeur authentifié)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'price'          => 'required|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'description'    => 'nullable|string',
            'category_id'    => 'nullable|exists:categories,id',
            'main_image'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();

        // upload de l'image principale
        $mainImagePath = null;
        if ($request->hasFile('main_image')) {
            $mainImagePath = $request->file('main_image')->store('products', 'public');
        }

        $product = Product::create([
            'user_id'        => $user->id,
            'company_id'     => $user->company->id ?? null,
            'name'           => $request->name,
            'slug'           => Str::slug($request->name . '-' . Str::random(5)),
            'price'          => $request->price,
            'stock'          => $request->stock,
            'description'    => $request->description,
            'category_id'    => $request->category_id,
            'main_image'     => $mainImagePath,
            'status'         => 'pending', // un admin pourra l’approuver plus tard
        ]);

        // Images secondaires si besoin
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json([
            'message' => 'Produit ajouté avec succès et en attente de validation.',
            'data' => $product->load('images'),
        ], 201);
    }

    /**
     * Détails d’un produit
     */
    public function show($id)
    {
        $product = Product::with(['company', 'images', 'category'])->findOrFail($id);
        return response()->json($product);
    }

    /**
     * Mise à jour du produit
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
            'main_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('main_image')) {
            if ($product->main_image && Storage::disk('public')->exists($product->main_image)) {
                Storage::disk('public')->delete($product->main_image);
            }
            $product->main_image = $request->file('main_image')->store('products', 'public');
        }

        $product->update($request->only([
            'name', 'price', 'stock', 'description', 'category_id'
        ]));

        return response()->json([
            'message' => 'Produit mis à jour avec succès.',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * Supprimer un produit
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        // supprimer les images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        if ($product->main_image) {
            Storage::disk('public')->delete($product->main_image);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès.']);
    }
}
