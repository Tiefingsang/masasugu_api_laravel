<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\BlockedUser;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * 📋 Liste des signalements
     */
    public function index(Request $request)
    {
        $query = Report::with('reporter:id,name,email');

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par type
        if ($request->filled('type')) {
            $typeMap = [
                'product' => Product::class,
                'user' => User::class,
            ];
            if (isset($typeMap[$request->type])) {
                $query->where('reportable_type', $typeMap[$request->type]);
            }
        }

        $reports = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'reviewed' => Report::where('status', 'reviewed')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
            'blocked_users' => BlockedUser::count(),
        ];

        return view('admin.reports.index', compact('reports', 'stats'));
    }

    /**
     * 👁️ Voir un signalement
     */
    public function show($id)
    {
        $report = Report::with('reporter')->findOrFail($id);

        // Charger le contenu signalé
        $target = null;
        if ($report->reportable_type === Product::class) {
            $target = Product::find($report->reportable_id);
        } elseif ($report->reportable_type === User::class) {
            $target = User::find($report->reportable_id);
        }

        return view('admin.reports.show', compact('report', 'target'));
    }

    /**
     * ✅ Marquer comme traité
     */
    public function resolve(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $report->update([
            'status' => 'resolved',
            'admin_note' => $request->input('admin_note'),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.reports.show', $id)
            ->with('success', 'Signalement marqué comme résolu.');
    }

    /**
     * ❌ Rejeter un signalement
     */
    public function reject(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $report->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.reports.show', $id)
            ->with('success', 'Signalement rejeté.');
    }

    /**
     * 🗑️ Supprimer un signalement
     */
    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return redirect()
            ->route('admin.reports.index')
            ->with('success', 'Signalement supprimé.');
    }

    /**
     * 🗑️ Supprimer le contenu signalé (produit/user)
     */
    public function deleteContent($id)
    {
        $report = Report::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($report->reportable_type === Product::class) {
                Product::where('id', $report->reportable_id)->delete();
            } elseif ($report->reportable_type === User::class) {
                // Bannir l'utilisateur
                User::where('id', $report->reportable_id)
                    ->update(['status' => 'banned']);
            }

            $report->update([
                'status' => 'resolved',
                'admin_note' => 'Contenu supprimé par l\'admin.',
                'reviewed_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.reports.index')
                ->with('success', 'Contenu supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * 🚫 Liste des utilisateurs bloqués
     */
    public function blockedUsers()
    {
        $blocked = BlockedUser::with(['blocker:id,name,email', 'blocked:id,name,email'])
            ->latest()
            ->paginate(20);

        return view('admin.reports.blocked', compact('blocked'));
    }

    /**
     * ✅ Débloquer un utilisateur
     */
    public function unblock($id)
    {
        $block = BlockedUser::findOrFail($id);
        $block->delete();

        return back()->with('success', 'Utilisateur débloqué.');
    }
}
