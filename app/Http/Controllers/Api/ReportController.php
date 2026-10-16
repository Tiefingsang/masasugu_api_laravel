<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\BlockedUser;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\NewReportNotification;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    /**
     * 📢 Signaler un contenu (produit, utilisateur, message)
     * POST /api/reports
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reportable_type' => 'required|in:product,user,message',
            'reportable_id' => 'required|integer',
            'reason' => 'required|in:spam,abuse,inappropriate,scam,fake,other',
            'description' => 'nullable|string|max:1000',
        ]);

        // Mapper le type
        $typeMap = [
            'product' => Product::class,
            'user' => User::class,
            'message' => \App\Models\Message::class,
        ];

        $type = $typeMap[$validated['reportable_type']];

        // Vérifier que la cible existe
        $target = $type::find($validated['reportable_id']);
        if (!$target) {
            return response()->json([
                'success' => false,
                'message' => 'Contenu introuvable.',
            ], 404);
        }

        // Empêcher l'auto-signalement
        if ($validated['reportable_type'] === 'user' &&
            $validated['reportable_id'] == $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous signaler vous-même.',
            ], 400);
        }

        // Vérifier si un signalement existe déjà
        $existing = Report::where('reporter_id', $request->user()->id)
            ->where('reportable_type', $type)
            ->where('reportable_id', $validated['reportable_id'])
            ->whereIn('status', ['pending', 'reviewed'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà signalé ce contenu. Notre équipe va le traiter.',
            ], 409);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $type,
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        $report = Report::create([
    'reporter_id' => $request->user()->id,
    'reportable_type' => $type,
    'reportable_id' => $validated['reportable_id'],
    'reason' => $validated['reason'],
    'description' => $validated['description'] ?? null,
    'status' => 'pending',
]);

// 📧 Envoyer un email à l'admin
try {
    Mail::to('supportmasasugu@gmail.com')->send(new NewReportNotification($report));
    \Log::info('📧 Email signalement envoyé à supportmasasugu@gmail.com');
} catch (\Exception $e) {
    \Log::error('❌ Erreur envoi email signalement : ' . $e->getMessage());
}

return response()->json([
    'success' => true,
    'message' => 'Signalement envoyé. Merci pour votre contribution.',
    'report_id' => $report->id,
], 201);
    }

    /**
     * 🚫 Bloquer un utilisateur
     * POST /api/users/{id}/block
     */
    public function block(Request $request, $id)
    {
        $userId = (int) $id;

        if ($userId === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous bloquer vous-même.',
            ], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        BlockedUser::firstOrCreate(
            [
                'blocker_id' => $request->user()->id,
                'blocked_id' => $userId,
            ],
            [
                'reason' => $request->input('reason'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur bloqué.',
        ]);
    }

    /**
     * ✅ Débloquer un utilisateur
     * DELETE /api/users/{id}/block
     */
    public function unblock(Request $request, $id)
    {
        BlockedUser::where('blocker_id', $request->user()->id)
            ->where('blocked_id', (int) $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur débloqué.',
        ]);
    }

    /**
     * 📋 Liste des utilisateurs bloqués
     * GET /api/blocked-users
     */
    public function blockedList(Request $request)
    {
        $blocked = BlockedUser::with('blocked:id,name,avatar')
            ->where('blocker_id', $request->user()->id)
            ->get();

        return response()->json([
            'success' => true,
            'blocked_users' => $blocked,
        ]);
    }
}
