<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    /**
     * Liste des boutiques avec filtres et recherche
     */
    public function index(Request $request)
    {
        $query = Company::with('user');

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par actif/inactif
        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes' ? 1 : 0);
        }

        // Recherche par nom, email ou téléphone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $shops = $query->orderByDesc('created_at')->paginate(15);

        // Compteurs pour les onglets
        $counts = [
            'all' => Company::count(),
            'pending' => Company::where('status', 'pending')->count(),
            'approved' => Company::where('status', 'approved')->count(),
            'rejected' => Company::where('status', 'rejected')->count(),
            'suspended' => Company::where('is_active', 0)->count(),
        ];

        return view('admin.shops.index', compact('shops', 'counts'));
    }

    /**
     * Détail d'une boutique
     */
    public function show($id)
    {
        $shop = Company::with([
            'user',
            'products' => function ($q) {
                $q->orderByDesc('created_at')->limit(20);
            },
            'orders' => function ($q) {
                $q->orderByDesc('created_at')->limit(10);
            }
        ])->findOrFail($id);

        // Statistiques de la boutique
        $stats = [
            'products_count' => $shop->products()->count(),
            'orders_count' => $shop->orders()->count(),
            'revenue' => $shop->orders()->where('status', 'delivered')->sum('total') ?? 0,
            'pending_orders' => $shop->orders()->where('status', 'pending')->count(),
        ];

        return view('admin.shops.show', compact('shop', 'stats'));
    }

    /**
     * Approuver une boutique
     */
    public function approve($id)
    {
        $shop = Company::findOrFail($id);

        $shop->update([
            'status' => 'approved',
            'is_active' => 1,
        ]);

        Log::info("Admin a approuvé la boutique #{$shop->id} ({$shop->name})");

        return redirect()
            ->route('admin.shops.index')
            ->with('success', "✅ La boutique « {$shop->name} » a été approuvée.");
    }

    /**
     * Refuser une boutique
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $shop = Company::findOrFail($id);

        $shop->update([
            'status' => 'rejected',
            'is_active' => 0,
        ]);

        Log::info("Admin a refusé la boutique #{$shop->id} ({$shop->name}) - Raison: {$request->reason}");

        return redirect()
            ->route('admin.shops.index')
            ->with('success', "❌ La boutique « {$shop->name} » a été refusée.");
    }

    /**
     * Suspendre une boutique (sans changer son statut)
     */
    public function suspend($id)
    {
        $shop = Company::findOrFail($id);

        $shop->update(['is_active' => 0]);

        Log::info("Admin a suspendu la boutique #{$shop->id} ({$shop->name})");

        return redirect()
            ->back()
            ->with('success', "⏸️ La boutique « {$shop->name} » a été suspendue.");
    }

    /**
     * Réactiver une boutique suspendue
     */
    public function activate($id)
    {
        $shop = Company::findOrFail($id);

        $shop->update(['is_active' => 1]);

        Log::info("Admin a réactivé la boutique #{$shop->id} ({$shop->name})");

        return redirect()
            ->back()
            ->with('success', "🔓 La boutique « {$shop->name} » a été réactivée.");
    }

    /**
     * Vérifier le vendeur (badge vérifié)
     */
    public function verifyUser($id)
    {
        $shop = Company::with('user')->findOrFail($id);

        if (!$shop->user) {
            return redirect()->back()->with('error', "❌ Aucun vendeur associé à cette boutique.");
        }

        $newStatus = !$shop->user->is_verified;

        $shop->user->update(['is_verified' => $newStatus ? 1 : 0]);

        // Met aussi à jour la boutique
        $shop->update(['is_verified' => $newStatus ? 1 : 0]);

        Log::info("Admin a " . ($newStatus ? 'vérifié' : 'dévérifié') . " le vendeur #{$shop->user->id}");

        $message = $newStatus
            ? "✅ Le vendeur a été vérifié."
            : "⚠️ Le vendeur a été dévérifié.";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Supprimer une boutique
     */
    public function destroy($id)
    {
        $shop = Company::findOrFail($id);
        $name = $shop->name;

        Log::warning("Admin a supprimé la boutique #{$shop->id} ({$name})");

        $shop->delete();

        return redirect()
            ->route('admin.shops.index')
            ->with('success', "🗑️ La boutique « {$name} » a été supprimée.");
    }
}
