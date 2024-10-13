<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = ['chat_id', 'message','image', 'sender_type', 'read_at'];

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }
}
