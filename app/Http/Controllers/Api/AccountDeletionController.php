<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountDeletionController extends Controller
{
    /**
     * 🗑️ Suppression de compte via le site web
     * Route : POST /api/account-deletion-web
     * Public (pas d'authentification requise)
     */
    public function destroyByEmail(Request $request)
    {
        // 1. Valider la requête
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // 2. Chercher l'utilisateur par email
        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte n\'est associé à cette adresse email.',
            ], 404);
        }

        // 3. Suppression en cascade
        try {
            DB::beginTransaction();

            // 3.1. Révoquer les tokens Sanctum
            $user->tokens()->delete();

            // 3.2. Interactions et préférences
            $user->interactions()->delete();
            if ($user->preferences) {
                $user->preferences()->delete();
            }

            // 3.3. Wallet et transactions
            $user->walletTransactions()->delete();
            if ($user->wallet) {
                $user->wallet()->delete();
            }

            // 3.4. Moyens de retrait et retraits
            $user->payoutMethods()->delete();
            $user->payouts()->delete();

            // 3.5. Boutique + produits + commandes + conversations
            if ($user->company) {
                $company = $user->company;

                if (method_exists($company, 'products')) {
                    $company->products()->delete();
                }
                if (method_exists($company, 'orders')) {
                    $company->orders()->delete();
                }
                if (method_exists($company, 'conversations')) {
                    $company->conversations()->delete();
                }

                $company->delete();
            }

            // 3.6. Panier
            DB::table('carts')->where('user_id', $user->id)->delete();

            // 3.7. Conversations de l'utilisateur
            DB::table('conversations')
                ->where('user_id', $user->id)
                ->delete();

            // 3.8. ⚠️ IMPORTANT : Supprimer l'utilisateur lui-même
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte supprimé avec succès.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur suppression compte web : ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression. Contactez le support.',
            ], 500);
        }
    }
}
