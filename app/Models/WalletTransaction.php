<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'seller_id',
        'type',
        'direction',
        'amount',
        'currency',
        'balance_before',
        'balance_after',
        'order_id',
        'transaction_id',
        'reference',
        'description',
        'metadata',
        'status',
        'available_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'available_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function wallet()
    {
        return $this->belongsTo(SellerWallet::class, 'wallet_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scopes
     */
    public function scopeCredits($query)
    {
        return $query->where('direction', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('direction', 'debit');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Accesseurs
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'SALE' => '💰 Vente',
            'COMMISSION' => '📊 Commission',
            'REFUND' => '↩️ Remboursement',
            'WITHDRAWAL' => '💸 Retrait',
            'ADJUSTMENT' => '⚙️ Ajustement',
            'RELEASE' => '🔓 Fonds libérés',
            'HOLD' => '⏸️ Fonds bloqués',
            'GATEWAY_FEE' => '💳 Frais paiement',
            default => $this->type,
        };
    }
}
