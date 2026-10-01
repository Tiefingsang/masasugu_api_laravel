<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * 📋 LISTE DES COMMANDES
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product.company']);

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par méthode de paiement
        if ($request->filled('payment')) {
            $query->where('payment_method', $request->payment);
        }

        // Filtre par date
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Recherche par ID ou nom client
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        // Compteurs
        $counts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        // Stats globales
        $stats = [
            'total_revenue' => Order::where('status', 'delivered')->sum('total') ?? 0,
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->where('status', 'delivered')
                ->sum('total') ?? 0,
            'avg_order' => Order::where('status', 'delivered')->avg('total') ?? 0,
        ];

        return view('admin.orders.index', compact('orders', 'counts', 'stats'));
    }

    /**
     * 📄 DÉTAIL D'UNE COMMANDE
     */
    public function show($id)
    {
        $order = Order::with([
            'user',
            'items.product.company',
            'items.product.images'
        ])->findOrFail($id);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * 🔄 CHANGER LE STATUT
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;

        $order->update(['status' => $request->status]);

        Log::info("🔄 Admin a changé le statut de la commande #{$order->id} : {$oldStatus} → {$request->status}");

        return redirect()
            ->back()
            ->with('success', "✅ Statut changé en « {$request->status} ».");
    }

    /**
     * ✅ CONFIRMER UNE COMMANDE
     */
    public function confirm($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'confirmed']);

        return redirect()->back()->with('success', "✅ Commande #{$order->id} confirmée.");
    }

    /**
     * 🚚 MARQUER COMME EXPÉDIÉE
     */
    public function ship($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'shipped']);

        return redirect()->back()->with('success', "🚚 Commande #{$order->id} marquée comme expédiée.");
    }

    /**
     * 📦 MARQUER COMME LIVRÉE
     */
    public function deliver($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'delivered']);

        return redirect()->back()->with('success', "📦 Commande #{$order->id} marquée comme livrée.");
    }

    /**
     * ❌ ANNULER UNE COMMANDE
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => 'cancelled']);

        Log::warning("❌ Admin a annulé la commande #{$order->id} - Raison: " . ($request->reason ?? 'Non spécifiée'));

        return redirect()
            ->back()
            ->with('success', "❌ Commande #{$order->id} annulée.");
    }

    /**
     * 🗑️ SUPPRIMER UNE COMMANDE
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        // Supprimer les items
        $order->items()->delete();
        $order->delete();

        Log::warning("🗑️ Admin a supprimé la commande #{$id}");

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "🗑️ Commande #{$id} supprimée.");
    }
}
