<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderCreatedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Canaux : database + broadcast
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * 📦 Représentation pour la base de données
     */
    public function toDatabase($notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'user_id'      => $this->order->user_id,
            'total'        => $this->order->total,
            'status'       => $this->order->status,
            'message'      => 'Nouvelle commande reçue #' . $this->order->id,
            'customer_name'=> $this->order->user->name ?? 'Un client',
            'product_name' => $this->order->items->first()->product->name ?? 'Produit',
            'product_image'=> $this->order->items->first()->product->main_image ?? null,
            'created_at'   => $this->order->created_at->toDateTimeString(),
        ];
    }

    /**
     * 📡 Représentation pour le broadcast Pusher
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'            => 'order.placed',
            'order_id'        => $this->order->id,
            'order_reference' => '#' . $this->order->id,
            'total'           => (float) $this->order->total,
            'currency'        => 'XOF',
            'status'          => $this->order->status,
            'message'         => 'Nouvelle commande reçue #' . $this->order->id,
            'customer_name'   => $this->order->user->name ?? 'Un client',
            'product_name'    => $this->order->items->first()->product->name ?? 'Produit',
            'product_image'   => $this->order->items->first()->product->main_image ?? null,
            'item_count'      => $this->order->items->count(),
            'created_at'      => $this->order->created_at->toDateTimeString(),
        ]);
    }

    /**
     * 🔔 Nom du channel broadcast
     * ✅ Format unifié : "chat.user.{id}" → devient "private-chat.user.{id}"
     */
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('chat.user.' . $notifiable->id),
        ];
    }

    /**
     * 📛 Nom de l'event côté Pusher
     */
    public function broadcastAs(): string
    {
        return 'order.placed';
    }
}
