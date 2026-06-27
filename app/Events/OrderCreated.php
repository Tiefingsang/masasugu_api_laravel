<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn()
    {
        // Récupérer le vendeur pour le broadcast
        $seller = \App\Models\User::where('company_id', $this->order->company_id)
            ->where('role', 'seller')
            ->first();

        if ($seller) {
            return new PrivateChannel('user.' . $seller->id);
        }

        return new PrivateChannel('user.' . $this->order->user_id);
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'company_id' => $this->order->company_id,
            'total' => $this->order->total,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at->toDateTimeString(),
            'items' => $this->order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
        ];
    }
}
