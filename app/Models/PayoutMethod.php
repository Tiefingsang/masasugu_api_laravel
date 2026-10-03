<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayoutMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'type',
        'destination',
        'label',
        'holder_name',
        'bank_name',
        'country_code',
        'currency',
        'is_default',
        'is_verified',
        'is_active',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'payout_method_id');
    }

    /**
     * Accesseurs
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'orange_money' => '🟠 Orange Money',
            'wave' => '🌊 Wave',
            'mtn_momo' => '📱 MTN MoMo',
            'moov_money' => '📱 Moov Money',
            'bank_transfer' => '🏦 Virement bancaire',
            default => $this->type,
        };
    }

    public function getMaskedDestinationAttribute(): string
    {
        $dest = $this->destination;
        if (strlen($dest) <= 6) {
            return $dest;
        }
        return substr($dest, 0, 4) . '****' . substr($dest, -2);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Boot : garantir qu'un seul default par vendeur
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($method) {
            if ($method->is_default) {
                static::where('seller_id', $method->seller_id)
                    ->where('id', '!=', $method->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
