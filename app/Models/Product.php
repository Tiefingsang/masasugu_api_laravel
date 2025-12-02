<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'specifications',
        'price',
        'discount_price',
        'currency',
        'stock',
        'is_available',
        'brand',
        'main_image',
        'status',
        'meta_title',
        'meta_keywords',
        'video',
        'meta_description',
        'rating',
        'views',
        'sales_count',
        'likes',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }

    
}
