<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ShopCategoryController extends Controller
{
    /**
     * Affiche toutes les catégories de boutique
     */
    public function index()
    {
        $categories = ShopCategory::all();
        return response()->json([
            'categories' => $categories
        ]);
    }
}
