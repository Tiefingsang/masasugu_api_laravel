<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Company;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'buyers' => User::where('role', 'buyer')->count(),
                'sellers' => User::where('role', 'seller')->count(),
                'pending' => User::where('is_verified', 0)->count(),
            ],
            'shops' => [
                'total' => Company::count(),
                'approved' => Company::where('status', 'approved')->count(),
                'pending' => Company::where('status', 'pending')->count(),
            ],
            'products' => [
                'total' => Product::count(),
                'approved' => Product::where('status', 'approved')->count(),
                'pending' => Product::where('status', 'pending')->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'revenue' => Order::where('status', 'delivered')->sum('total') ?? 0,
            ],
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
