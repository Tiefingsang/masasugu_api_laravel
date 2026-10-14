<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 🗑️ Suppression du compte utilisateur (RGPD / Google Play)
     * Route : DELETE /api/user
     */
    public function destroy(Request $request)
    {
        // 1. Valider la requête
        $validated = $request->validate([
            'password'     => 'required|string',
            'confirmation' => 'required|string|in:SUPPRIMER',
        ]);

        $user = $request->user();

        // 2. Vérifier le mot de passe
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect.',
            ], 403);
        }

        // 3. Suppression en cascade dans une transaction
        try {
            DB::beginTransaction();

            // 3.1. Révoquer tous les tokens Sanctum
            $user->tokens()->delete();

            // 3.2. Supprimer les interactions et préférences
            $user->interactions()->delete();
            if ($user->preferences) {
                $user->preferences()->delete();
            }

            // 3.3. Supprimer le wallet et transactions (seller_id)
            $user->walletTransactions()->delete();
            if ($user->wallet) {
                $user->wallet()->delete();
            }

            // 3.4. Supprimer les moyens de retrait et retraits
            $user->payoutMethods()->delete();
            $user->payouts()->delete();

            // 3.5. Supprimer la boutique + ses produits/commandes
            if ($user->company) {
                $company = $user->company;

                // Produits de la boutique
                if (method_exists($company, 'products')) {
                    $company->products()->delete();
                }

                // Commandes liées à la boutique
                if (method_exists($company, 'orders')) {
                    $company->orders()->delete();
                }

                // Conversations liées à la boutique
                if (method_exists($company, 'conversations')) {
                    $company->conversations()->delete();
                }

                $company->delete();
            }

            // 3.6. Supprimer le panier (cart items)
            DB::table('carts')->where('user_id', $user->id)->delete();

            // 3.7. Supprimer les conversations de l'utilisateur (en tant qu'acheteur)
            DB::table('conversations')
                ->where('user_id', $user->id)
                ->delete();

            // 3.8. ⚠️ IMPORTANT : Supprimer l'utilisateur lui-même
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Compte supprimé avec succès.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur suppression compte : ' . $e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de la suppression. Contactez le support.',
            ], 500);
        }
    }
}
