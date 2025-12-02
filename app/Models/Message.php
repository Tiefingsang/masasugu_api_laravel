<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
      'conversation_id','sender_id','receiver_id','content','type','metadata','read_at'
    ];

    protected $casts = [
      'metadata' => 'array',
      'read_at' => 'datetime',
    ];

    public function conversation(){ return $this->belongsTo(Conversation::class); }
    public function sender(){ return $this->belongsTo(User::class,'sender_id'); }
    public function receiver(){ return $this->belongsTo(User::class,'receiver_id'); }
}
