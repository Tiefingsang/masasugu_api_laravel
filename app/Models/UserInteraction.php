<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'category_id',
        'type',
        'weight',
        'keyword',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    // ─── Relations ───
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // ─── Pondérations par type ───
    public static function weightFor(string $type): int
    {
        return match ($type) {
            'view'        => 1,
            'search'      => 2,
            'like'        => 3,
            'share'       => 4,
            'add_to_cart' => 5,
            'purchase'    => 10,
            'unlike'      => -3,
            default       => 1,
        };
    }
}
