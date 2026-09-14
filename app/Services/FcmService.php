<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase-credentials.json'));

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendToUser($user, string $title, string $body, array $data = [])
    {
        if (empty($user->fcm_token)) {
            Log::warning("⚠️ User {$user->id} n'a pas de token FCM");
            return false;
        }

        try {
            // Convertir toutes les valeurs en string (FCM exige string)
            $stringData = array_map(fn($v) => (string) $v, $data);

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($stringData)
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'masasugu_high_importance',
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ])
                );

            $this->messaging->send($message);

            Log::info("✅ FCM envoyé à user {$user->id}", [
                'title' => $title,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("❌ FCM erreur: " . $e->getMessage(), [
                'user_id' => $user->id,
                'token' => $user->fcm_token,
            ]);
            return false;
        }
    }

    /**
     * Envoyer à plusieurs utilisateurs
     */
    public function sendToUsers($users, string $title, string $body, array $data = [])
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }
}
