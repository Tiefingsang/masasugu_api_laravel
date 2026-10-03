<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'currency_code',
        'phone_code',
        'flag',
        'region',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'country', 'code');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'country', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
