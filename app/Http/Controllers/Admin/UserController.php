<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 📋 LISTE DES UTILISATEURS
     * Filtres : rôle, statut, vérifié, recherche
     */
    public function index(Request $request)
    {
        $query = User::with('company');

        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre vérifié
        if ($request->filled('verified')) {
            $query->where('is_verified', $request->verified === 'yes' ? 1 : 0);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20);

        // Compteurs
        $counts = [
            'all' => User::count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'sellers' => User::where('role', 'seller')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'verified' => User::where('is_verified', 1)->count(),
            'pending' => User::where('is_verified', 0)->count(),
            'banned' => User::where('status', 'banned')->count(),
        ];

        return view('admin.users.index', compact('users', 'counts'));
    }

    /**
     * 📄 DÉTAIL D'UN UTILISATEUR
     */
    public function show($id)
    {
        $user = User::with(['company'])->findOrFail($id);

        // Stats de l'utilisateur
        $stats = [
            'orders_count' => Order::where('user_id', $user->id)->count(),
            'orders_total' => Order::where('user_id', $user->id)->sum('total') ?? 0,
            'products_count' => $user->company ? $user->company->products()->count() : 0,
        ];

        // Dernières commandes
        $recentOrders = Order::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.users.show', compact('user', 'stats', 'recentOrders'));
    }

    /**
     * ✏️ FORMULAIRE D'ÉDITION
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * 💾 METTRE À JOUR UN UTILISATEUR
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:buyer,seller,admin',
            'status' => 'required|in:active,inactive,banned',
            'bio' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
            'bio' => $request->bio,
            'country' => $request->country,
            'city' => $request->city,
            'address' => $request->address,
        ]);

        Log::info("Admin a mis à jour l'utilisateur #{$user->id}");

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', "✅ L'utilisateur « {$user->name} » a été mis à jour.");
    }

    /**
     * 👤 VÉRIFIER / DÉVÉRIFIER UN UTILISATEUR
     */
    public function verify($id)
    {
        $user = User::findOrFail($id);

        $newStatus = !$user->is_verified;
        $user->update(['is_verified' => $newStatus ? 1 : 0]);

        // Si seller, met aussi à jour sa boutique
        if ($user->role === 'seller' && $user->company) {
            $user->company->update(['is_verified' => $newStatus ? 1 : 0]);
        }

        Log::info("Admin a " . ($newStatus ? 'vérifié' : 'dévérifié') . " l'utilisateur #{$user->id}");

        $message = $newStatus
            ? "✅ L'utilisateur « {$user->name} » a été vérifié."
            : "⚠️ L'utilisateur « {$user->name} » a été dévérifié.";

        return redirect()->back()->with('success', $message);
    }

    /**
     * 🚫 BANNIR UN UTILISATEUR
     */
    public function ban(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return redirect()->back()->with('error', "❌ Impossible de bannir un administrateur.");
        }

        $user->update([
            'status' => 'banned',
            'is_active' => 0,
        ]);

        // Suspend aussi sa boutique si seller
        if ($user->role === 'seller' && $user->company) {
            $user->company->update(['is_active' => 0]);
        }

        Log::warning("Admin a banni l'utilisateur #{$user->id} - Raison: " . ($request->reason ?? 'Non spécifiée'));

        return redirect()
            ->route('admin.users.index')
            ->with('success', "🚫 L'utilisateur « {$user->name} » a été banni.");
    }

    /**
     * ✅ DÉBANNIR UN UTILISATEUR
     */
    public function unban($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status' => 'active',
            'is_active' => 1,
        ]);

        // Réactive sa boutique si seller
        if ($user->role === 'seller' && $user->company) {
            $user->company->update(['is_active' => 1]);
        }

        Log::info("Admin a débanni l'utilisateur #{$user->id}");

        return redirect()
            ->route('admin.users.index')
            ->with('success', "✅ L'utilisateur « {$user->name} » a été débanni.");
    }

    /**
     * 🔄 CHANGER LE RÔLE
     */
    public function changeRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:buyer,seller,admin',
        ]);

        $user = User::findOrFail($id);
        $oldRole = $user->role;

        $user->update(['role' => $request->role]);

        Log::info("Admin a changé le rôle de l'utilisateur #{$user->id} : {$oldRole} → {$request->role}");

        return redirect()
            ->back()
            ->with('success', "✅ Le rôle a été changé en « {$request->role} ».");
    }

    /**
     * 🔑 RÉINITIALISER LE MOT DE PASSE
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        Log::warning("Admin a réinitialisé le mot de passe de l'utilisateur #{$user->id}");

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', "🔑 Le mot de passe a été réinitialisé.");
    }

    /**
     * 🗑️ SUPPRIMER UN UTILISATEUR
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return redirect()->back()->with('error', "❌ Impossible de supprimer un administrateur.");
        }

        $name = $user->name;

        // Supprime la boutique associée si seller
        if ($user->role === 'seller' && $user->company) {
            $user->company->delete();
        }

        $user->delete();

        Log::warning("Admin a supprimé l'utilisateur #{$id} ({$name})");

        return redirect()
            ->route('admin.users.index')
            ->with('success', "🗑️ L'utilisateur « {$name} » a été supprimé.");
    }
}
