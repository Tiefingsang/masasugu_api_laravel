<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * 📋 LISTE DES PRODUITS
     */
    public function index(Request $request)
    {
        $query = Product::with(['company', 'category', 'user']);

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par catégorie
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filtre par boutique
        if ($request->filled('company')) {
            $query->where('company_id', $request->company);
        }

        // Filtre disponibilité
        if ($request->filled('available')) {
            $query->where('is_available', $request->available === 'yes' ? 1 : 0);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $products = $query->orderByDesc('created_at')->paginate(20);

        $counts = [
            'all' => Product::count(),
            'pending' => Product::where('status', 'pending')->count(),
            'approved' => Product::where('status', 'approved')->count(),
            'rejected' => Product::where('status', 'rejected')->count(),
            'out_of_stock' => Product::where('stock', 0)->count(),
        ];

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'counts', 'categories'));
    }

    /**
     * 📄 DÉTAIL D'UN PRODUIT
     */
    public function show($id)
    {
        $product = Product::with([
            'company.user',
            'category',
            'images',
            'user'
        ])->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    /**
     * ✅ APPROUVER UN PRODUIT
     */
    public function approve($id)
    {
        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'approved',
            'is_available' => 1,
        ]);

        Log::info("✅ Admin a approuvé le produit #{$product->id} ({$product->name})");

        return redirect()
            ->back()
            ->with('success', "✅ Le produit « {$product->name} » a été approuvé.");
    }

    /**
     * ❌ REFUSER UN PRODUIT
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'rejected',
            'is_available' => 0,
        ]);

        Log::warning("❌ Admin a refusé le produit #{$product->id} - Raison: {$request->reason}");

        return redirect()
            ->back()
            ->with('success', "❌ Le produit « {$product->name} » a été refusé.");
    }

    /**
     * 🚫 RENDRE INDISPONIBLE
     */
    public function disable($id)
    {
        $product = Product::findOrFail($id);

        $product->update(['is_available' => 0]);

        Log::info("🚫 Admin a désactivé le produit #{$product->id}");

        return redirect()
            ->back()
            ->with('success', "🚫 Le produit « {$product->name} » est maintenant indisponible.");
    }

    /**
     * ✅ RENDRE DISPONIBLE
     */
    public function enable($id)
    {
        $product = Product::findOrFail($id);

        $product->update(['is_available' => 1]);

        Log::info("✅ Admin a activé le produit #{$product->id}");

        return redirect()
            ->back()
            ->with('success', "✅ Le produit « {$product->name} » est maintenant disponible.");
    }

    /**
     * ⭐ METTRE EN AVANT
     */
    public function feature($id)
    {
        $product = Product::findOrFail($id);

        $newStatus = !$product->is_featured;
        $product->update(['is_featured' => $newStatus ? 1 : 0]);

        $message = $newStatus
            ? "⭐ Le produit a été mis en avant."
            : "Le produit n'est plus mis en avant.";

        return redirect()->back()->with('success', $message);
    }

    /**
     * 🗑️ SUPPRIMER UN PRODUIT
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;

        // Supprimer les images
        foreach ($product->images as $image) {
            $image->delete();
        }

        $product->delete();

        Log::warning("🗑️ Admin a supprimé le produit #{$id} ({$name})");

        return redirect()
            ->route('admin.products.index')
            ->with('success', "🗑️ Le produit « {$name} » a été supprimé.");
    }
}
