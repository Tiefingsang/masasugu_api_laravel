<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::select('id', 'name')->get();

        return response()->json($categories);
    }

    public function getCategory(){
        return response()->json(Category::all());
    }

    public function getProductsByCategory($id){
        $products = Product::where('category_id', $id)->get();
        return response()->json($products);
    }

}
