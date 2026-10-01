<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * 📋 PAGE PRINCIPALE — Historique + Formulaire
     */
    public function index(Request $request)
    {
        // Récupérer les notifications récentes (si tu utilises Laravel Notifications)
        // Sinon, on peut créer une table dédiée plus tard
        $recentNotifications = collect(); // placeholder

        // Stats
        $stats = [
            'total_users' => User::count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'sellers' => User::where('role', 'seller')->count(),
            'with_fcm' => User::whereNotNull('fcm_token')->count(),
        ];

        return view('admin.notifications.index', compact('stats', 'recentNotifications'));
    }

    /**
     * 📤 ENVOYER UNE NOTIFICATION
     */
    public function send(Request $request)
    {
        $request->validate([
            'target' => 'required|in:all,buyers,sellers,specific',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'user_id' => 'required_if:target,specific|nullable|integer|exists:users,id',
            'type' => 'required|in:info,promo,alert,update',
        ]);

        // Déterminer les destinataires
        $query = User::whereNotNull('fcm_token');

        switch ($request->target) {
            case 'buyers':
                $query->where('role', 'buyer');
                break;
            case 'sellers':
                $query->where('role', 'seller');
                break;
            case 'specific':
                $query->where('id', $request->user_id);
                break;
            case 'all':
            default:
                // tous les utilisateurs avec FCM
                break;
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', '❌ Aucun destinataire trouvé avec un token FCM.');
        }

        // Envoyer via FCM
        $fcm = new FcmService();
        $successCount = 0;
        $failCount = 0;

        foreach ($recipients as $user) {
            try {
                $result = $fcm->sendToUser(
                    $user,
                    $request->title,
                    $request->message,
                    [
                        'type' => 'admin_' . $request->type,
                        'title' => $request->title,
                        'message' => $request->message,
                    ]
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\Exception $e) {
                Log::error("Erreur envoi notification à user #{$user->id}: {$e->getMessage()}");
                $failCount++;
            }
        }

        Log::info("📤 Admin a envoyé une notification : {$request->title} — {$successCount} succès, {$failCount} échecs");

        return redirect()
            ->back()
            ->with('success', "✅ Notification envoyée : {$successCount} succès, {$failCount} échecs.");
    }

    /**
     * 🧪 TEST — Envoyer une notification à un utilisateur spécifique
     */
    public function test(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
        ]);

        $user = User::find($request->user_id);

        if (!$user->fcm_token) {
            return redirect()->back()->with('error', "❌ L'utilisateur n'a pas de token FCM.");
        }

        $fcm = new FcmService();

        try {
            $result = $fcm->sendToUser(
                $user,
                $request->title,
                $request->message,
                ['type' => 'test']
            );

            if ($result) {
                return redirect()->back()->with('success', "✅ Test réussi — notification envoyée à {$user->name}.");
            } else {
                return redirect()->back()->with('error', "❌ Échec de l'envoi.");
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "❌ Erreur: {$e->getMessage()}");
        }
    }
}
