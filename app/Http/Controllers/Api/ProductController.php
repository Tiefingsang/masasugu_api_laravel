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
    /* public function index(Request $request)
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

    $products = $query->paginate(20);

    // 🔹 Ajouter l’URL complète de l’image
    $products->getCollection()->transform(function ($product) {
        $product->main_image_url = $product->main_image
            ? asset('storage/' . $product->main_image)
            : null;
        return $product;
    });

    return response()->json($products);
}
    /**
     * Créer un produit (par un vendeur authentifié)
     */
    
 public function store(Request $request)
{
    $user = $request->user(); // 🔹 récupère l'utilisateur connecté grâce au token

    $request->validate([
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'discount_price' => 'nullable|numeric|min:0',
        'stock' => 'required|integer|min:0',
        'brand' => 'nullable|string|max:100',
        'main_image' => 'nullable|file|mimes:jpg,jpeg,png|',
    ]);

    // 🔹 Création du slug unique
    $slug = Str::slug($request->name) . '-' . Str::random(5);

    $product = Product::create([
        'user_id' => $user->id,
        'company_id' => $user->company_id, // 🔹 auto-lié à la boutique du user
        'category_id' => $request->category_id,
        'name' => $request->name,
        'slug' => $slug,
        'sku' => strtoupper(Str::random(10)),
        'description' => $request->description,
        'price' => $request->price,
        'discount_price' => $request->discount_price,
        'currency' => $request->currency ?? 'XOF',
        'stock' => $request->stock,
        'is_available' => true,
        'brand' => $request->brand,
        'status' => 'approved',
    ]);

    // 🔹 Si une image principale est envoyée
    if ($request->hasFile('main_image')) {
        $path = $request->file('main_image')->store('products/main', 'public');
        $product->update(['main_image' => $path]);
    }

    return response()->json([
        'message' => '✅ Produit créé avec succès',
        'product' => $product,
    ], 201);
}






    /**
     * Détails d’un produit
     */
    /* public function show($id)
    {
        $product = Product::with(['company', 'images', 'category'])->findOrFail($id);
        return response()->json($product);
    } */
   public function show($id)
    {
        $product = Product::with(['company', 'images', 'category'])->findOrFail($id);

        $product->main_image_url = $product->main_image
            ? asset('storage/' . $product->main_image)
            : null;

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
     * Télécharger une vidéo pour le produit
     */
    public function uploadVideo(Request $request, $id)
    {
        $request->validate([
        'video' => 'nullable|file|mimetypes:video/mp4,video/x-msvideo,video/quicktime,video/x-matroska,video/webm,video/ogg|max:512000',
    ]);


    if ($request->hasFile('video')) {
        $path = $request->file('video')->store('products/videos', 'public');

        $product = Product::findOrFail($id);
        $product->update(['video_path' => $path]);

        return response()->json([
            'message' => '✅ Vidéo uploadée avec succès',
            'video_url' => asset('storage/' . $path),
        ]);
    }

    return response()->json([
        'message' => 'Aucune vidéo trouvée ou erreur lors de l’upload.'
    ], 422);
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
