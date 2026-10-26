<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\PaymentException;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Services\Payments\PayoutManager;
use App\Services\Payments\WalletManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PayoutController — Gestion des retraits vendeurs
 *
 * Endpoints :
 * - GET    /api/payout-methods         → Lister les moyens
 * - POST   /api/payout-methods         → Ajouter un moyen
 * - DELETE /api/payout-methods/{id}    → Supprimer un moyen
 * - POST   /api/payouts/request        → Demander un retrait
 * - GET    /api/payouts                → Historique des retraits
 */
class PayoutController extends Controller
{
    protected PayoutManager $payoutManager;
    protected WalletManager $walletManager;

    public function __construct()
    {
        $this->payoutManager = new PayoutManager();
        $this->walletManager = new WalletManager();
    }

    /**
     * 📋 GET /api/payout-methods
     */
    public function indexMethods()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $methods = PayoutMethod::where('seller_id', $user->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'type_label' => $method->type_label,
                    'destination' => $method->masked_destination,
                    'label' => $method->label,
                    'holder_name' => $method->holder_name,
                    'bank_name' => $method->bank_name,
                    'country_code' => $method->country_code,
                    'currency' => $method->currency,
                    'is_default' => $method->is_default,
                    'is_verified' => $method->is_verified,
                ];
            });

        return response()->json([
            'methods' => $methods,
            'total' => $methods->count(),
        ]);
    }

    /**
     * ➕ POST /api/payout-methods
     *
     * Ajoute un nouveau moyen de retrait.
     *
     * Body:
     * {
     *     "type": "orange_money",
     *     "destination": "7701901164",
     *     "label": "Mon Orange Money",
     *     "holder_name": "SANGARE Tiefing",
     *     "country_code": "ML",
     *     "is_default": true
     * }
     */
    public function storeMethod(Request $request)
    {
        $request->validate([
            'type' => 'required|in:orange_money,wave,mtn_momo,moov_money,bank_transfer',
            'destination' => 'required|string|max:255',
            'label' => 'nullable|string|max:100',
            'holder_name' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|size:2',
            'is_default' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Si c'est le premier moyen, il devient par défaut
        $isFirstMethod = PayoutMethod::where('seller_id', $user->id)->count() === 0;

        // Si is_default est true, on retire le défaut des autres
        if ($request->is_default || $isFirstMethod) {
            PayoutMethod::where('seller_id', $user->id)->update(['is_default' => false]);
        }

        $method = PayoutMethod::create([
            'seller_id' => $user->id,
            'type' => $request->type,
            'destination' => $request->destination,
            'label' => $request->label,
            'holder_name' => $request->holder_name ?? $user->name,
            'bank_name' => $request->bank_name,
            'country_code' => $request->country_code ?? $user->country ?? 'ML',
            'currency' => 'XOF',
            'is_default' => $request->is_default ?? $isFirstMethod,
            'is_verified' => false,
            'is_active' => true,
        ]);

        Log::info('➕ Moyen de retrait ajouté', [
            'seller_id' => $user->id,
            'method_id' => $method->id,
            'type' => $method->type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Moyen de retrait ajouté',
            'method' => [
                'id' => $method->id,
                'type' => $method->type,
                'type_label' => $method->type_label,
                'destination' => $method->masked_destination,
                'is_default' => $method->is_default,
            ],
        ], 201);
    }

    /**
     * 🗑️ DELETE /api/payout-methods/{id}
     */
    public function destroyMethod($id)
    {
        $user = Auth::user();

        $method = PayoutMethod::where('id', $id)
            ->where('seller_id', $user->id)
            ->first();

        if (!$method) {
            return response()->json(['error' => 'Moyen de retrait introuvable'], 404);
        }

        $wasDefault = $method->is_default;

        $method->update(['is_active' => false, 'is_default' => false]);

        // Si c'était le moyen par défaut, on en désigne un autre
        if ($wasDefault) {
            $newDefault = PayoutMethod::where('seller_id', $user->id)
                ->where('is_active', true)
                ->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Moyen de retrait supprimé']);
    }

    /**
     * 💸 POST /api/payouts/request
     *
     * Demande un retrait.
     *
     * Body:
     * {
     *     "payout_method_id": 1,
     *     "amount": 50000
     * }
     */
    public function requestPayout(Request $request)
    {
        $request->validate([
            'payout_method_id' => 'required|exists:payout_methods,id',
            'amount' => 'required|numeric|min:1000', // Minimum 1000 XOF
        ]);

        $user = Auth::user();

        // 1. Récupérer le wallet et le moyen de retrait
        $wallet = $this->walletManager->getOrCreateWallet($user->id);
        $method = PayoutMethod::where('id', $request->payout_method_id)
            ->where('seller_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$method) {
            return response()->json(['error' => 'Moyen de retrait introuvable'], 404);
        }

        // 2. Vérifier le solde disponible
        if ($wallet->available_balance < $request->amount) {
            return response()->json([
                'error' => 'Solde insuffisant. Votre solde disponible est de ' . $wallet->available_balance . ' XOF.'
            ], 400);
        }

        try {
            // 3. Créer la demande de retrait (PayoutManager gère l'appel PayDunya)
            $payout = $this->payoutManager->requestPayout($wallet, $method, (float) $request->amount);

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait enregistrée',
                'payout' => [
                    'payout_id' => $payout->payout_id,
                    'amount' => (float) $payout->amount,
                    'status' => $payout->status,
                    'status_label' => $payout->status_label,
                    'method' => $payout->method,
                    'destination' => $payout->destination,
                    'requested_at' => $payout->requested_at,
                ],
            ], 201);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('❌ Erreur retrait', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du retrait : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📜 GET /api/payouts
     *
     * Historique des retraits du vendeur.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $payouts = Payout::forSeller($user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'payouts' => $payouts->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'payout_id' => $payout->payout_id,
                    'amount' => (float) $payout->amount,
                    'fee' => (float) $payout->fee,
                    'net_amount' => (float) $payout->net_amount,
                    'currency' => $payout->currency,
                    'method' => $payout->method,
                    'destination' => $payout->destination,
                    'status' => $payout->status,
                    'status_label' => $payout->status_label,
                    'status_color' => $payout->status_color,
                    'requested_at' => $payout->requested_at,
                    'completed_at' => $payout->completed_at,
                ];
            }),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }


        public function handlePayDunyaPayoutWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('🔔 Webhook Payout PayDunya reçu', $payload);

        // Vérifier la signature (optionnel mais recommandé)
        $expectedHash = hash('sha512', config('services.paydunya.master_key'));
        if (isset($payload['hash']) && $payload['hash'] !== $expectedHash) {
            return response('Invalid signature', 403);
        }

        $payoutId = $payload['custom_data']['payout_id'] ?? null;
        $status = $payload['status'] ?? null;

        if ($payoutId && $status === 'completed') {
            $payout = Payout::where('payout_id', $payoutId)->first();
            if ($payout && $payout->status !== 'completed') {
                $this->payoutManager->complete($payout, $payload['disburse_token'] ?? null);
            }
        }

        return response('OK', 200);
    }
}
