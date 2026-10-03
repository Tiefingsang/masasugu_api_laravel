<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerWallet;
use App\Models\WalletTransaction;
use App\Services\Payments\WalletManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * WalletController — Gestion des wallets vendeurs
 *
 * Endpoints :
 * - GET /api/wallet/balance      → Solde du wallet
 * - GET /api/wallet/transactions → Historique des mouvements
 * - GET /api/wallet/stats        → Statistiques
 */
class WalletController extends Controller
{
    protected WalletManager $manager;

    public function __construct()
    {
        $this->manager = new WalletManager();
    }

    /**
     * 💰 GET /api/wallet/balance
     *
     * Retourne le solde du wallet du vendeur connecté.
     */
    public function balance()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $wallet = $this->manager->getOrCreateWallet($user->id, 'XOF');

        return response()->json([
            'wallet' => [
                'id' => $wallet->id,
                'currency' => $wallet->currency,
                'available_balance' => (float) $wallet->available_balance,
                'pending_balance' => (float) $wallet->pending_balance,
                'total_balance' => (float) $wallet->total_balance,
                'total_earned' => (float) $wallet->total_earned,
                'total_withdrawn' => (float) $wallet->total_withdrawn,
                'total_commission_paid' => (float) $wallet->total_commission_paid,
                'last_transaction_at' => $wallet->last_transaction_at,
            ],
        ]);
    }

    /**
     * 📜 GET /api/wallet/transactions
     *
     * Retourne l'historique des mouvements du wallet.
     * Filtres : type, direction, page
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $wallet = $this->manager->getOrCreateWallet($user->id);

        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'transactions' => $transactions->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'type' => $txn->type,
                    'type_label' => $txn->type_label,
                    'direction' => $txn->direction,
                    'amount' => (float) $txn->amount,
                    'currency' => $txn->currency,
                    'balance_before' => (float) $txn->balance_before,
                    'balance_after' => (float) $txn->balance_after,
                    'description' => $txn->description,
                    'reference' => $txn->reference,
                    'order_id' => $txn->order_id,
                    'status' => $txn->status,
                    'created_at' => $txn->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * 📊 GET /api/wallet/stats
     *
     * Statistiques du wallet (revenus, commissions, etc.)
     */
    public function stats()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $wallet = $this->manager->getOrCreateWallet($user->id);

        // Ventes des 30 derniers jours
        $last30Days = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'SALE')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');

        // Ventes des 7 derniers jours
        $last7Days = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'SALE')
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('amount');

        // Commissions payées
        $totalCommission = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'COMMISSION')
            ->sum('amount');

        return response()->json([
            'stats' => [
                'available_balance' => (float) $wallet->available_balance,
                'pending_balance' => (float) $wallet->pending_balance,
                'total_balance' => (float) $wallet->total_balance,
                'total_earned' => (float) $wallet->total_earned,
                'total_withdrawn' => (float) $wallet->total_withdrawn,
                'total_commission_paid' => (float) $wallet->total_commission_paid,
                'sales_last_30_days' => (float) $last30Days,
                'sales_last_7_days' => (float) $last7Days,
                'total_commission' => (float) abs($totalCommission),
            ],
        ]);
    }
}
