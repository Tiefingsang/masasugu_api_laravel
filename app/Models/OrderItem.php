<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'seller_id',
        'product_id',
        'quantity',
        'price',
        'platform_fee',
        'gateway_fee',
        'seller_amount',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'seller_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    /**
     * Relation : chaque ligne de commande appartient à une commande
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : chaque ligne concerne un produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation : vendeur (utilisateur) qui reçoit l'argent
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Relation : boutique (company) du vendeur
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relation : transactions liées à cet item
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
