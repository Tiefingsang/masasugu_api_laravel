<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'symbol',
        'decimals',
        'is_active',
    ];

    protected $casts = [
        'decimals' => 'integer',
        'is_active' => 'boolean',
    ];

    public function countries()
    {
        return $this->hasMany(Country::class, 'currency_code', 'code');
    }

    /**
     * Formater un montant : $currency->format(5000) → "5 000 FCFA"
     */
    public function format(float $amount): string
    {
        $formatted = number_format($amount, $this->decimals, ',', ' ');
        return "{$formatted} {$this->symbol}";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
