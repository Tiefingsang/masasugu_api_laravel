<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'top_categories',
        'top_brands',
        'top_keywords',
        'price_range',
        'tracking_enabled',
        'last_computed_at',
    ];

    protected $casts = [
        'top_categories'  => 'array',
        'top_brands'      => 'array',
        'top_keywords'    => 'array',
        'price_range'     => 'array',
        'tracking_enabled'=> 'boolean',
        'last_computed_at'=> 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🕒 Vérifie si le cache est expiré (> 1h)
     */
    public function isStale(): bool
    {
        if (!$this->last_computed_at) return true;
        return $this->last_computed_at->diffInMinutes(now()) > 60;
    }
}
