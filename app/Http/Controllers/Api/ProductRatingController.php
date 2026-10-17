<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRating;
use Illuminate\Http\Request;

class ProductRatingController extends Controller
{
    // 📌 Lister les avis d'un produit
    public function index(Product $product)
    {
        $ratings = ProductRating::with('user:id,name,avatar')
            ->where('product_id', $product->id)
            ->latest()
            ->paginate(10);

        $average = $product->ratings()->avg('rating') ?? 0;
        $total = $product->ratings()->count();

        return response()->json([
            'average_rating' => round($average, 2),
            'total_ratings' => $total,
            'data' => $ratings->items(),
            'current_page' => $ratings->currentPage(),
            'last_page' => $ratings->lastPage(),
        ]);
    }

    // 📌 Créer/modifier un avis
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = ProductRating::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        // Recalculer la moyenne
        $average = $product->ratings()->avg('rating');
        $product->update(['rating' => $average]);

        return response()->json([
            'message' => 'Avis enregistré',
            'data' => $rating->load('user:id,name,avatar'),
        ], 201);
    }
}
