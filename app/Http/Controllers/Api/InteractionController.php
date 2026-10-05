<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserInteraction;
use App\Models\UserPreference;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InteractionController extends Controller
{
    /**
     * 📥 Enregistrer une interaction utilisateur
     *
     * POST /api/interactions
     * Body: { product_id?, category_id?, type, keyword? }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'type'        => 'required|in:view,like,unlike,add_to_cart,purchase,search,share',
            'keyword'     => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Non authentifié'], 401);
        }

        // 🚫 Privacy : si tracking désactivé → on ignore
        $prefs = UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['tracking_enabled' => true]
        );

        if (!$prefs->tracking_enabled) {
            return response()->json(['ok' => true, 'tracked' => false]);
        }

        // 🎯 Auto-remplir category_id depuis product_id si absent
        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId && !empty($validated['product_id'])) {
            $categoryId = Product::find($validated['product_id'])?->category_id;
        }

        // 🎯 Enregistrer l'interaction
        $interaction = UserInteraction::create([
            'user_id'     => $user->id,
            'product_id'  => $validated['product_id'] ?? null,
            'category_id' => $categoryId,
            'type'        => $validated['type'],
            'weight'      => UserInteraction::weightFor($validated['type']),
            'keyword'     => $validated['keyword'] ?? null,
        ]);

        Log::info('🎯 Interaction enregistrée', [
            'user_id' => $user->id,
            'type'    => $validated['type'],
            'product' => $validated['product_id'] ?? null,
            'category'=> $categoryId,
        ]);

        return response()->json([
            'ok' => true,
            'tracked' => true,
            'interaction_id' => $interaction->id,
        ]);
    }

    /**
     * 📤 Enregistrer plusieurs interactions d'un coup (batch)
     *
     * POST /api/interactions/batch
     * Body: { interactions: [{ product_id, type, ... }, ...] }
     */
    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'interactions' => 'required|array|max:50',
            'interactions.*.product_id'  => 'nullable|integer|exists:products,id',
            'interactions.*.category_id' => 'nullable|integer|exists:categories,id',
            'interactions.*.type'        => 'required|in:view,like,unlike,add_to_cart,purchase,search,share',
            'interactions.*.keyword'     => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false], 401);
        }

        $prefs = UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['tracking_enabled' => true]
        );

        if (!$prefs->tracking_enabled) {
            return response()->json(['ok' => true, 'tracked' => 0]);
        }

        $rows = [];
        $now = now();

        foreach ($validated['interactions'] as $i) {
            $categoryId = $i['category_id'] ?? null;
            if (!$categoryId && !empty($i['product_id'])) {
                $categoryId = Product::find($i['product_id'])?->category_id;
            }

            $rows[] = [
                'user_id'     => $user->id,
                'product_id'  => $i['product_id'] ?? null,
                'category_id' => $categoryId,
                'type'        => $i['type'],
                'weight'      => UserInteraction::weightFor($i['type']),
                'keyword'     => $i['keyword'] ?? null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        UserInteraction::insert($rows);

        return response()->json([
            'ok' => true,
            'tracked' => count($rows),
        ]);
    }

    /**
     * 📊 Récupérer mes préférences (debug)
     */
    public function myPreferences(Request $request)
    {
        $user = $request->user();
        $prefs = UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            ['tracking_enabled' => true]
        );

        return response()->json([
            'preferences' => $prefs,
            'interactions_count' => UserInteraction::where('user_id', $user->id)->count(),
        ]);
    }

    /**
     * 🔕 Activer / désactiver le tracking (RGPD)
     */
    public function toggleTracking(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $prefs = UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['tracking_enabled' => $validated['enabled']]
        );

        return response()->json([
            'ok' => true,
            'tracking_enabled' => $prefs->tracking_enabled,
        ]);
    }
}
