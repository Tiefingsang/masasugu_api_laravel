<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_id',
        'seller_id',
        'wallet_id',
        'payout_method_id',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'method',
        'destination',
        'holder_name',
        'bank_name',
        'status',
        'rejection_reason',
        'admin_note',
        'provider',
        'provider_reference',
        'provider_response',
        'requested_at',
        'processed_at',
        'completed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot : générer automatiquement le payout_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            if (empty($payout->payout_id)) {
                $payout->payout_id = 'PAY-' . strtoupper(Str::random(12));
            }
            if (empty($payout->requested_at)) {
                $payout->requested_at = now();
            }
        });
    }

    /**
     * Relations
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function wallet()
    {
        return $this->belongsTo(SellerWallet::class, 'wallet_id');
    }

    public function method()
    {
        return $this->belongsTo(PayoutMethod::class, 'payout_method_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Accesseurs
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => '⏳ En attente',
            'processing' => '🔄 En traitement',
            'completed' => '✅ Complété',
            'failed' => '❌ Échoué',
            'cancelled' => '🚫 Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'orange',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Helper : marquer comme complété
     */
       public function markAsCompleted(?string $providerReference = null): void
    {
        $this->update([
            'status' => 'completed',
            'provider_reference' => $providerReference ?? $this->provider_reference,
            'processed_at' => $this->processed_at ?? now(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Helper : marquer comme échoué
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'rejection_reason' => $reason,
            'processed_at' => $this->processed_at ?? now(),
        ]);
    }
}
