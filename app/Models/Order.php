<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'total',
        'subtotal',
        'platform_fee_total',
        'gateway_fee_total',
        'currency',
        'country',
        'paid_at',
        'status',
        'payment_status',
        'payment_method',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'platform_fee_total' => 'decimal:2',
        'gateway_fee_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Relation : une commande appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : une commande a plusieurs articles
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation : une commande appartient à une boutique (legacy)
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relation : les items groupés par vendeur
     */
    public function itemsBySeller()
    {
        return $this->items()->with('seller', 'company')->get()->groupBy('seller_id');
    }

    /**
 * Relation : transactions liées à cette commande
 */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
