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
        $credentialsPath = storage_path('app/firebase-credentials.json');

        if (!file_exists($credentialsPath)) {
            throw new \Exception('firebase-credentials.json introuvable dans storage/app/');
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendToUser($user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            Log::warning("User {$user->id} n'a pas de token FCM");
            return false;
        }

        try {
            $stringData = array_map(fn($v) => (string) $v, $data);

            // Version 8.x : CloudMessage::new() + withToken()
            $message = CloudMessage::new()
                ->withToken($user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($stringData)
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'channel_id'   => 'masasugu_high_importance',
                            'sound'        => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ])
                );

            $this->messaging->send($message);

            Log::info("FCM envoye a user {$user->id}", ['title' => $title]);
            return true;
        } catch (\Exception $e) {
            Log::error("FCM erreur: " . $e->getMessage(), [
                'user_id' => $user->id,
                'token'   => substr($user->fcm_token, 0, 20) . '...',
            ]);
            return false;
        }
    }

    /**
     * Envoyer à plusieurs utilisateurs
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }
}
