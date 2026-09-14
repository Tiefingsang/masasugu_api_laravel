<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function broadcastOn(): array
    {
        // Récupérer le vendeur
        $seller = \App\Models\User::where('company_id', $this->order->company_id)
            ->where('role', 'seller')
            ->first();

        if ($seller) {
            return [new PrivateChannel('chat.user.' . $seller->id)];
        }

        return [new PrivateChannel('chat.user.' . $this->order->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'        => $this->order->id,
            'reference'       => '#' . $this->order->id,
            'total'           => (float) $this->order->total,
            'currency'        => 'XOF',
            'customer_name'   => $this->order->user->name ?? 'Un client',
            'product_name'    => $this->order->items->first()->product->name ?? 'Produit',
            'product_image'   => $this->order->items->first()->product->main_image ?? null,
            'item_count'      => $this->order->items->count(),
        ];
    }
}
