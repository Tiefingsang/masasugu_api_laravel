<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // 🔍 RECHERCHE
    // ═══════════════════════════════════════════════════════════
    public function search(Request $request)
    {
        $query = $request->query('q');
        $produits = Product::where('name', 'like', "%{$query}%")->get();

        return response()->json(['produits' => $produits]);
    }

    private function normalizeKeywords(array $keywords): array
    {
        $map = [
            'shirt' => ['tshirt', 't-shirt', 'chemise', 'haut'],
            'clothing' => ['vetement', 'vêtements', 'habit'],
            'shoe' => ['chaussure', 'basket', 'sneaker'],
            'phone' => ['telephone', 'smartphone', 'mobile'],
            'laptop' => ['ordinateur', 'pc'],
        ];

        $final = [];
        foreach ($keywords as $word) {
            $final[] = $word;
            if (isset($map[$word])) {
                $final = array_merge($final, $map[$word]);
            }
        }
        return array_unique($final);
    }

    public function searchByKeywords(Request $request)
    {
        $keywords = $request->input('keywords', []);
        $keywords = array_map('strtolower', $keywords);
        $keywords = $this->normalizeKeywords($keywords);

        if (!is_array($keywords) || empty($keywords)) {
            return response()->json([
                'data' => [],
                'message' => 'No keywords provided',
            ]);
        }

        $query = Product::with(['category', 'images'])
            ->where('status', 'approved')
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $word = strtolower($word);
                    $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$word}%"])
                        ->orWhereHas('category', function ($cat) use ($word) {
                            $cat->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        });
                }
            });

        return response()->json([
            'data' => $query->limit(20)->get(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 📋 INDEX — Liste paginée
    // ═══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $query = Product::with(['company', 'user', 'images', 'category'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $products = $query->paginate(20);

        $products->getCollection()->transform(function ($product) {
            $product->main_image_url = $product->main_image
                ? asset('storage/' . $product->main_image)
                : null;

            $product->vendor_phone =
                ($product->company->phone ?? null)
                ?: ($product->user->phone ?? null);

            $product->likes_list = $product->likes;
            $product->likes = $product->likes()->count();
            $product->is_liked = $product->likes()
                ->where('user_id', auth()->id())
                ->exists();

            return $product;
        });

        return response()->json($products);
    }

    // ═══════════════════════════════════════════════════════════
    // 🆕 STORE — Créer un produit (avec images + vidéo)
    // ═══════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $user = $request->user();

        // ✅ Validation complète
        $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'brand'          => 'nullable|string|max:100',
            'main_image'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'extra_images'   => 'nullable|array|max:5',
            'extra_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
            'video'          => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:51200',
        ]);

        // 🔹 Slug unique
        $slug = Str::slug($request->name) . '-' . Str::random(5);

        // 1. Créer le produit
        $product = Product::create([
            'user_id'        => $user->id,
            'company_id'     => $user->company_id,
            'category_id'    => $request->category_id,
            'name'           => $request->name,
            'slug'           => $slug,
            'sku'            => strtoupper(Str::random(10)),
            'description'    => $request->description,
            'price'          => $request->price,
            'discount_price' => $request->discount_price,
            'currency'       => $request->currency ?? 'XOF',
            'stock'          => $request->stock,
            'is_available'   => true,
            'brand'          => $request->brand,
            'status'         => 'approved',
        ]);

        // 2. Image principale
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products/main', 'public');
            $product->update(['main_image' => $path]);
        }

        // 3. ✅ Images supplémentaires
        if ($request->hasFile('extra_images')) {
            foreach ($request->file('extra_images') as $image) {
                $path = $image->store('products/images', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                ]);
            }
        }

        // 4. ✅ Vidéo du produit
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('products/videos', 'public');
            $product->update(['video' => $path]);
        }

        // 5. 🔔 Notifier les acheteurs
        try {
            $fcm = new \App\Services\FcmService();
            $count = $fcm->notifyNewProduct($product, $user->id);
            Log::info('Nouveau produit annonce', [
                'product_id' => $product->id,
                'notified'   => $count,
            ]);
        } catch (\Exception $e) {
            Log::warning('Erreur FCM nouveau produit: ' . $e->getMessage());
        }

        return response()->json([
            'message' => '✅ Produit créé avec succès',
            'product' => $product->load('images'),
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════
    // 👁️ SHOW — Détails d'un produit
    // ═══════════════════════════════════════════════════════════
    public function show($id)
    {
        $product = Product::with(['company', 'images', 'category'])->findOrFail($id);
        return response()->json($product);
    }

    // ═══════════════════════════════════════════════════════════
    // ✏️ UPDATE — Mise à jour (avec images + vidéo)
    // ═══════════════════════════════════════════════════════════
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 🔒 Autorisation
        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        // ✅ Validation
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'stock'          => 'required|integer|min:0',
            'brand'          => 'nullable|string|max:255',
            'discount_price' => 'nullable|numeric|min:0',
            'category_id'    => 'required|integer|exists:categories,id',
            'main_image'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'extra_images'   => 'nullable|array|max:5',
            'extra_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'video'          => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:51200',
        ]);

        // ✅ Nouvelle image principale
        if ($request->hasFile('main_image')) {
            if ($product->main_image &&
                Storage::disk('public')->exists($product->main_image)) {
                Storage::disk('public')->delete($product->main_image);
            }

            $path = $request->file('main_image')->store('products/main', 'public');
            $validated['main_image'] = $path;
        }

        // ✅ Nouvelle vidéo (remplacer l'ancienne)
        if ($request->hasFile('video')) {
            if ($product->video &&
                Storage::disk('public')->exists($product->video)) {
                Storage::disk('public')->delete($product->video);
            }

            $path = $request->file('video')->store('products/videos', 'public');
            $validated['video'] = $path;
        }

        // ✅ Mise à jour du produit
        $product->update($validated);

        // ✅ Ajouter les nouvelles images supplémentaires
        if ($request->hasFile('extra_images')) {
            foreach ($request->file('extra_images') as $image) {
                $path = $image->store('products/images', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès.',
            'product' => $product->fresh()->load('images'),
        ], 200);
    }

    // ═══════════════════════════════════════════════════════════
    // 🎥 UPLOAD VIDEO — Endpoint dédié
    // ═══════════════════════════════════════════════════════════
    public function uploadVideo(Request $request, $id)
    {
        $request->validate([
            'video' => 'nullable|file|mimetypes:video/mp4,video/x-msvideo,video/quicktime,video/x-matroska,video/webm,video/ogg|max:512000',
        ]);

        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('products/videos', 'public');

            $product = Product::findOrFail($id);

            // Supprimer l'ancienne vidéo
            if ($product->video &&
                Storage::disk('public')->exists($product->video)) {
                Storage::disk('public')->delete($product->video);
            }

            $product->update(['video' => $path]);

            return response()->json([
                'message' => '✅ Vidéo uploadée avec succès',
                'video_url' => asset('storage/' . $path),
            ]);
        }

        return response()->json([
            'message' => 'Aucune vidéo trouvée ou erreur lors de l\'upload.',
        ], 422);
    }

    // ═══════════════════════════════════════════════════════════
    // 🏪 GET PRODUCTS BY SHOP
    // ═══════════════════════════════════════════════════════════
    public function getProductsByShop($company_id)
    {
        $products = Product::where('company_id', $company_id)
            ->where('status', 'approved')
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->get();

        $products->transform(function ($product) {
            // Image principale
            if ($product->main_image && !str_starts_with($product->main_image, 'http')) {
                $product->main_image = asset('storage/' . $product->main_image);
            }

            // Vidéo
            if ($product->video && !str_starts_with($product->video, 'http')) {
                $product->video = asset('storage/' . $product->video);
            }

            // Images secondaires
            if ($product->images && count($product->images) > 0) {
                foreach ($product->images as $image) {
                    if ($image->image_path && !str_starts_with($image->image_path, 'http')) {
                        $image->image_path = asset('storage/' . $image->image_path);
                    }
                }
            }

            return $product;
        });

        return response()->json([
            'data' => $products,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🗑️ DESTROY — Supprimer un produit
    // ═══════════════════════════════════════════════════════════
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        // Supprimer les images secondaires
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        // Supprimer l'image principale
        if ($product->main_image) {
            Storage::disk('public')->delete($product->main_image);
        }

        // Supprimer la vidéo
        if ($product->video) {
            Storage::disk('public')->delete($product->video);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès.']);
    }

    // ═══════════════════════════════════════════════════════════
    // 🔥 BEST OFFERS
    // ═══════════════════════════════════════════════════════════
    public function bestOffers()
    {
        $products = Product::whereNotNull('discount_price')
            ->where('discount_price', '>', 0)
            ->orderByRaw("(price - discount_price) DESC")
            ->take(20)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ⭐ TOP RATED
    // ═══════════════════════════════════════════════════════════
    public function topRated()
    {
        $products = Product::orderBy('rating', 'desc')
            ->orderBy('views', 'desc')
            ->orderBy('sales_count', 'desc')
            ->orderBy('likes', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // 🕒 RECENTS
    // ═══════════════════════════════════════════════════════════
    public function addToRecent($id)
    {
        $user = auth()->user();

        $user->recentProducts()->syncWithoutDetaching([$id]);

        Log::info("Produit ajouté au récent", [
            'user_id' => $user->id,
            'product_id' => $id,
        ]);

        return response()->json(['message' => 'Added']);
    }

    public function recents()
    {
        $user = auth()->user();
        $recents = $user->recentProducts()->latest()->take(10)->get();

        Log::info("Produits récents récupérés", [
            'user_id' => $user->id,
            'count' => $recents->count(),
        ]);

        return response()->json($recents);
    }

    // ═══════════════════════════════════════════════════════════
    // 🆕 NEW PRODUCTS
    // ═══════════════════════════════════════════════════════════
    public function newProducts()
    {
        $products = Product::orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $products,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ❤️ TOGGLE LIKE
    // ═══════════════════════════════════════════════════════════
    public function toggleLike($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $product = Product::findOrFail($id);
        $alreadyLiked = $product->likes()->where('user_id', $user->id)->exists();

        if ($alreadyLiked) {
            $product->likes()->where('user_id', $user->id)->delete();
            $liked = false;
        } else {
            $product->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes' => $product->likes()->count(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // ⭐ RATE PRODUCT
    // ═══════════════════════════════════════════════════════════
    public function rateProduct(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $product = Product::findOrFail($id);
        $user = auth()->user();

        $product->ratings()->updateOrCreate(
            ['user_id' => $user->id],
            ['rating' => $request->rating]
        );

        $average = $product->ratings()->avg('rating');
        $product->update(['rating' => $average]);

        return response()->json([
            'success' => true,
            'rating' => $average,
        ]);
    }
}
