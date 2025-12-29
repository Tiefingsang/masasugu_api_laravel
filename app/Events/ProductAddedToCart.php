<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ProductAddedToCart implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Product $product;
    public int $sellerId;

    /**
     * Create a new event instance.
     */
    public function __construct(Product $product, int $sellerId)
    {
        $this->product = $product;
        $this->sellerId = $sellerId;
    }


    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'seller_id' => $this->sellerId,
        ];
    }


    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        \Log::info('Broadcast ProductAddedToCart', [
    'sellerId' => $this->sellerId
    ]);
        return [
            new PrivateChannel('vendor.' . $this->sellerId),
        ];
        

    }

     public function broadcastAs(): string
    {
        return 'ProductAddedToCart';
    }
}
