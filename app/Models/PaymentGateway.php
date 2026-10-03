<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'currencies',
        'gateway',
        'display_name',
        'payment_method',
        'provider',
        'config',
        'priority',
        'logo_url',
        'description',
        'is_active',
        'is_sandbox',
    ];

    protected $casts = [
        'currencies' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Masquer les clés API sensibles
     */
    protected $hidden = [
        // On ne cache PAS config car on en a besoin côté serveur
        // Mais on pourrait ajouter un accesseur si nécessaire
    ];

    /**
     * Relation : pays
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Récupérer une clé de config
     * 
     * Usage : $gateway->getConfig('merchant_key')
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Scope : gateways actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : gateways de production
     */
    public function scopeProduction($query)
    {
        return $query->where('is_sandbox', false);
    }

    /**
     * Scope : gateways sandbox
     */
    public function scopeSandbox($query)
    {
        return $query->where('is_sandbox', true);
    }

    /**
     * Scope : par pays
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }
}
