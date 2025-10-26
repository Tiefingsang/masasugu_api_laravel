<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'image' => 'required|file|mimes:jpg,jpeg,png,gif', 
    ]);

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('products/images', 'public');

        $image = \App\Models\ProductImage::create([
            'product_id' => $request->product_id,
            'image_path' => $path,
        ]);

        return response()->json([
            'message' => '✅ Image ajoutée avec succès',
            'image' => $image,
        ], 201);
    }

    return response()->json(['message' => 'Aucune image trouvée'], 422);
}


}
