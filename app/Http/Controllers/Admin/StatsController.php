<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Company;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30');
        $startDate = match ($period) {
            '7' => Carbon::now()->subDays(7),
            '30' => Carbon::now()->subDays(30),
            '90' => Carbon::now()->subDays(90),
            '365' => Carbon::now()->subYear(),
            'all' => Carbon::create(2020, 1, 1),
            default => Carbon::now()->subDays(30),
        };

        // ─── STATS GLOBALES ───
        $globalStats = [
            'users' => [
                'total' => User::count(),
                'new_period' => User::where('created_at', '>=', $startDate)->count(),
                'buyers' => User::where('role', 'buyer')->count(),
                'sellers' => User::where('role', 'seller')->count(),
                'admins' => User::where('role', 'admin')->count(),
                'verified' => User::where('is_verified', 1)->count(),
            ],
            'shops' => [
                'total' => Company::count(),
                'new_period' => Company::where('created_at', '>=', $startDate)->count(),
                'approved' => Company::where('status', 'approved')->count(),
                'pending' => Company::where('status', 'pending')->count(),
                'rejected' => Company::where('status', 'rejected')->count(),
            ],
            'products' => [
                'total' => Product::count(),
                'new_period' => Product::where('created_at', '>=', $startDate)->count(),
                'approved' => Product::where('status', 'approved')->count(),
                'pending' => Product::where('status', 'pending')->count(),
                'out_of_stock' => Product::where('stock', 0)->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'new_period' => Order::where('created_at', '>=', $startDate)->count(),
                'pending' => Order::where('status', 'pending')->count(),
                'confirmed' => Order::where('status', 'confirmed')->count(),
                'shipped' => Order::where('status', 'shipped')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],
            'revenue' => [
                'total' => Order::where('status', 'delivered')->sum('total') ?? 0,
                'period' => Order::where('status', 'delivered')
                    ->where('created_at', '>=', $startDate)
                    ->sum('total') ?? 0,
                'avg_order' => Order::where('status', 'delivered')->avg('total') ?? 0,
                'pending' => Order::where('status', 'pending')->sum('total') ?? 0,
            ],
        ];

        // ─── GRAPHIQUE : Commandes par jour ───
        $ordersByDay = Order::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(CASE WHEN status = "delivered" THEN total ELSE 0 END), 0) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ─── NOUVEAUX UTILISATEURS PAR MOIS ───
        $usersByMonth = User::where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ─── TOP 5 VENDEURS (optimisé) ───
        $topSellers = DB::table('users')
            ->join('companies', 'users.id', '=', 'companies.user_id')
            ->leftJoin('products', 'companies.id', '=', 'products.company_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.id', '=', 'order_items.order_id')
                     ->where('orders.status', '=', 'delivered');
            })
            ->where('users.role', 'seller')
            ->select(
                'users.id',
                'users.name',
                'companies.name as company_name',
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('COALESCE(SUM(orders.total), 0) as revenue')
            )
            ->groupBy('users.id', 'users.name', 'companies.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ─── TOP 5 PRODUITS ───
        $topProducts = Product::with('company')
            ->orderByDesc('sales_count')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        // ─── TOP CATÉGORIES ───
        $topCategories = Category::withCount('products')
            ->orderByDesc('products_count')
            ->limit(8)
            ->get();

        // ─── RÉPARTITION RÔLES ───
        $rolesDistribution = [
            'buyers' => $globalStats['users']['buyers'],
            'sellers' => $globalStats['users']['sellers'],
            'admins' => $globalStats['users']['admins'],
        ];

        // ─── RÉPARTITION COMMANDES ───
        $ordersDistribution = [
            'pending' => $globalStats['orders']['pending'],
            'confirmed' => $globalStats['orders']['confirmed'],
            'shipped' => $globalStats['orders']['shipped'],
            'delivered' => $globalStats['orders']['delivered'],
            'cancelled' => $globalStats['orders']['cancelled'],
        ];

        // ─── TOP 5 VILLES ───
        $topCities = Company::select('city', DB::raw('COUNT(*) as count'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('admin.stats.index', compact(
            'globalStats',
            'ordersByDay',
            'usersByMonth',
            'topSellers',
            'topProducts',
            'topCategories',
            'rolesDistribution',
            'ordersDistribution',
            'topCities',
            'period'
        ));
    }
}
