<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'order_id',
        'order_item_id',
        'buyer_id',
        'seller_id',
        'company_id',
        'gateway',
        'gateway_transaction_id',
        'payment_method',
        'provider',
        'country_code',
        'currency',
        'amount',
        'platform_fee',
        'gateway_fee',
        'seller_amount',
        'status',
        'type',
        'error_message',
        'metadata',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'seller_amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Boot : générer automatiquement le transaction_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN-' . strtoupper(Str::random(12));
            }
        });
    }

    /**
     * Relations
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Scopes
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }
}
