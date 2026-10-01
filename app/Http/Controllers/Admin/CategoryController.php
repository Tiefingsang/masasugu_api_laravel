<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * 📋 LISTE DES CATÉGORIES
     */
    public function index(Request $request)
    {
        $query = Category::withCount('products');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * ➕ FORMULAIRE CRÉATION
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * 💾 ENREGISTRER
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);

        Log::info("✅ Admin a créé la catégorie #{$category->id} ({$category->name})");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "✅ La catégorie « {$category->name} » a été créée.");
    }

    /**
     * ✏️ FORMULAIRE D'ÉDITION
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * 💾 METTRE À JOUR
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        Log::info("✅ Admin a mis à jour la catégorie #{$category->id}");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "✅ La catégorie a été mise à jour.");
    }

    /**
     * 🗑️ SUPPRIMER
     */
    public function destroy($id)
    {
        $category = Category::withCount('products')->findOrFail($id);

        // Vérifier qu'aucun produit n'est associé
        if ($category->products_count > 0) {
            return redirect()
                ->back()
                ->with('error', "❌ Impossible de supprimer : {$category->products_count} produit(s) utilisent cette catégorie.");
        }

        $name = $category->name;
        $category->delete();

        Log::warning("🗑️ Admin a supprimé la catégorie #{$id} ({$name})");

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "🗑️ La catégorie « {$name} » a été supprimée.");
    }
}
