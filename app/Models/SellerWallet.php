<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SellerWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'currency',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_withdrawn',
        'total_commission_paid',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'total_commission_paid' => 'decimal:2',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')
            ->orderBy('created_at', 'desc');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'wallet_id');
    }

    /**
     * Solde total (disponible + en attente)
     */
    public function getTotalBalanceAttribute(): float
    {
        return (float) ($this->available_balance + $this->pending_balance);
    }

    /**
     * Scope : wallets actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : par devise
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

   
}
