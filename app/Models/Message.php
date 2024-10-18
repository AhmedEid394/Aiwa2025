<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $primaryKey = 'message_id';
    protected $fillable = ['chat_id', 'message','file', 'sender_type', 'sender_id','read_at'];

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }
}
