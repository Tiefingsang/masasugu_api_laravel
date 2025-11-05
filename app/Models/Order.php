<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'total',
        'status',
        'payment_method',

    ];

    /**
     *  Relation : une commande appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     *  Relation : une commande a plusieurs articles
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    // Relation : un article de commande appartient à un produit

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function shop(){
        return $this->belongsTo(Compagny::class, 'company_id'); 
    }

    public function company()
{
    return $this->belongsTo(Company::class);
}


    


}
