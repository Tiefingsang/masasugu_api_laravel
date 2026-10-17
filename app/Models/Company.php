<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;

class Company extends Model
{
    use HasFactory;
    use Notifiable;

    protected $appends = ['logo_url', 'whatsapp_phone', 'banner_url'];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'country',
        'city',
        'postal_code',
        'address',
        'license_number',
        'certification_documents',
        'website',
        'facebook',
        'instagram',
        'tiktok',
        'is_verified',
        'is_factory',
        'is_active',
        'status',
        'payment_methods',
        'contact_email',
        'contact_phone',
        'shop_category_id',
    ];

    // ✅ Un seul $casts, fusionné
    protected $casts = [
        'certification_documents' => 'array',
        'payment_methods' => 'array',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
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
     * Scope : boutiques actives et validées
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }

    /**
     * ✅ URL complète du logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        return 'https://api.masasugu.com/storage/' . ltrim($this->logo, '/');
    }

    /**
     * ✅ URL complète de la bannière
     */
    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) {
            return null;
        }

        if (str_starts_with($this->banner, 'http')) {
            return $this->banner;
        }

        return 'https://api.masasugu.com/storage/' . ltrim($this->banner, '/');
    }

    /**
     * 📞 Numéro WhatsApp effectif de la boutique
     * Priorité : contact_phone → user.phone
     */
    public function getWhatsappPhoneAttribute(): ?string
    {
        if (!empty($this->contact_phone)) {
            return $this->contact_phone;
        }
        if ($this->user && !empty($this->user->phone)) {
            return $this->user->phone;
        }
        return null;
    }
}
