<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLike extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
