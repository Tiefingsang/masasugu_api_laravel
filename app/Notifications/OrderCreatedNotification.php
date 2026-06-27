<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'total' => $this->order->total,
            'status' => $this->order->status,
            'message' => 'Nouvelle commande reçue #' . $this->order->id,
            'created_at' => $this->order->created_at->toDateTimeString(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'order_id' => $this->order->id,
                'user_id' => $this->order->user_id,
                'total' => $this->order->total,
                'status' => $this->order->status,
                'message' => 'Nouvelle commande reçue #' . $this->order->id,
                'created_at' => $this->order->created_at->toDateTimeString(),
            ]
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('Nouvelle commande reçue !')
                    ->line('Commande #' . $this->order->id)
                    ->line('Total: ' . $this->order->total . ' FCFA')
                    ->action('Voir la commande', url('/mes-commandes'))
                    ->line('Merci d\'utiliser notre plateforme !');
    }
}
