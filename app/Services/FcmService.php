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
     * 📤 Envoyer une notification générique à un utilisateur
     */
    public function sendToUser($user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            Log::warning("User {$user->id} sans token FCM");
            return false;
        }

        try {
            $stringData = array_map(fn($v) => (string) $v, $data);

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
            Log::error("FCM erreur: " . $e->getMessage(), ['user_id' => $user->id]);
            return false;
        }
    }

    public function sendToUsers($users, string $title, string $body, array $data = []): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 🔔 NOTIFICATIONS DE COMMANDE — Chaque étape du cycle de vie
    // ═══════════════════════════════════════════════════════════

    /**
     * 🛍️ Nouvelle commande → destinataire : VENDEUR
     */
    public function notifyNewOrder($order, $seller, $buyer): bool
    {
        $productName = $order->items->first()->product->name ?? 'Produit';

        return $this->sendToUser(
            $seller,
            '🛍️ Nouvelle commande',
            "{$buyer->name} a commandé : {$productName}",
            [
                'type'          => 'order',
                'order_id'      => (string) $order->id,
                'total'         => (string) $order->total,
                'currency'      => 'XOF',
                'customer_name' => (string) $buyer->name,
                'product_name'  => (string) $productName,
                'product_image' => (string) ($order->items->first()->product->main_image ?? ''),
                'reference'     => '#' . $order->id,
                'status'        => 'pending',
            ]
        );
    }

    /**
     * ✅ Commande confirmée → destinataire : ACHETEUR
     */
    public function notifyOrderConfirmed($order): bool
    {
        if (!$order->user) return false;

        return $this->sendToUser(
            $order->user,
            '✅ Commande confirmée',
            "Votre commande #{$order->id} est confirmée et en préparation",
            [
                'type'      => 'order_status',
                'order_id'  => (string) $order->id,
                'status'    => 'confirmee',
                'reference' => '#' . $order->id,
            ]
        );
    }

    /**
     * 🚚 Commande expédiée → destinataire : ACHETEUR
     */
    public function notifyOrderShipped($order): bool
    {
        if (!$order->user) return false;

        return $this->sendToUser(
            $order->user,
            '🚚 Commande expédiée',
            "Votre commande #{$order->id} est en route",
            [
                'type'      => 'order_status',
                'order_id'  => (string) $order->id,
                'status'    => 'expediee',
                'reference' => '#' . $order->id,
            ]
        );
    }

    /**
     * 📦 Commande livrée → destinataire : ACHETEUR
     */
    public function notifyOrderDelivered($order): bool
    {
        if (!$order->user) return false;

        return $this->sendToUser(
            $order->user,
            '📦 Commande livrée',
            "Votre commande #{$order->id} a été livrée. Merci !",
            [
                'type'      => 'order_status',
                'order_id'  => (string) $order->id,
                'status'    => 'livree',
                'reference' => '#' . $order->id,
            ]
        );
    }

    /**
     * ❌ Commande annulée → destinataire : ACHETEUR
     */
    public function notifyOrderCancelled($order): bool
    {
        if (!$order->user) return false;

        return $this->sendToUser(
            $order->user,
            '❌ Commande annulée',
            "Votre commande #{$order->id} a été annulée",
            [
                'type'      => 'order_status',
                'order_id'  => (string) $order->id,
                'status'    => 'annulee',
                'reference' => '#' . $order->id,
            ]
        );
    }

    /**
     * 🗑️ Commande supprimée → destinataire : ACHETEUR
     */
    public function notifyOrderDeleted($order): bool
    {
        if (!$order->user) return false;

        return $this->sendToUser(
            $order->user,
            '🗑️ Commande supprimée',
            "Votre commande #{$order->id} a été supprimée",
            [
                'type'      => 'order_deleted',
                'order_id'  => (string) $order->id,
                'reference' => '#' . $order->id,
            ]
        );
    }


        /**
     * 🆕 Nouveau produit → destinataire : tous les ACHETEURS (sauf le vendeur)
     */
    public function notifyNewProduct($product, $vendorId): int
    {
        // Récupérer tous les acheteurs actifs avec un token FCM
        $buyers = \App\Models\User::where('role', 'buyer')
            ->where('id', '!=', $vendorId)
            ->whereNotNull('fcm_token')
            ->get();

        if ($buyers->isEmpty()) {
            Log::info('Aucun acheteur avec FCM pour notifier le nouveau produit');
            return 0;
        }

        $count = 0;
        foreach ($buyers as $buyer) {
            try {
                $sent = $this->sendToUser(
                    $buyer,
                    '🆕 Nouveau produit disponible',
                    $product->name . ' — ' . number_format($product->price, 0, ',', ' ') . ' XOF',
                    [
                        'type'         => 'new_product',
                        'product_id'   => (string) $product->id,
                        'product_name' => (string) $product->name,
                        'product_image' => (string) ($product->main_image ?? ''),
                        'price'        => (string) $product->price,
                        'currency'     => (string) ($product->currency ?? 'XOF'),
                        'vendor_id'    => (string) $vendorId,
                    ]
                );

                if ($sent) $count++;
            } catch (\Exception $e) {
                Log::warning("Erreur FCM nouveau produit pour user {$buyer->id}: " . $e->getMessage());
            }
        }

        Log::info("FCM nouveau produit envoye a {$count} acheteurs", [
            'product_id' => $product->id,
        ]);

        return $count;
    }
}
