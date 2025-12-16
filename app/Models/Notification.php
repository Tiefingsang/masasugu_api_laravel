<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'vendor_id', 'type', 'message', 'data', 'is_read'
    ];
    
    protected $casts = [
        'data' => 'array'
    ];
}
