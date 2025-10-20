<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'country',
        'city',
        'postal_code',
        'address',
        'license_number',
        'website',
        'facebook',
        'instagram',
        'tiktok',
        'is_verified',
        'is_active',
        'status',
        'contact_email',
        'contact_phone',
        'shop_category_id',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* public function category()
    {
        return $this->belongsTo(Category::class);
    } */

    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }


    /**
     * Génération automatique du slug à partir du nom
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name . '-' . uniqid());
            }
        });
    }

    /**
     * Scope : récupérer uniquement les boutiques actives et validées
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }
}
