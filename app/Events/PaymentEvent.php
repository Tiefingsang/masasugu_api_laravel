<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PaymentEvent — Événement générique pour tous les événements de paiement
 *
 * Diffusé via Reverb (WebSocket) pour le temps réel.
 */
class PaymentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventName;
    public array $data;

    public function __construct(string $eventName, array $data = [])
    {
        $this->eventName = $eventName;
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('payments'),
        ];

        if (isset($this->data['seller_id'])) {
            $channels[] = new PrivateChannel('seller.' . $this->data['seller_id']);
        }

        if (isset($this->data['buyer_id'])) {
            $channels[] = new PrivateChannel('buyer.' . $this->data['buyer_id']);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return array_merge($this->data, [
            'event' => $this->eventName,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
