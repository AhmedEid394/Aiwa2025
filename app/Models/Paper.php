<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paper extends Model
{
    use HasFactory;
    protected $primaryKey = 'paper_id';
    protected $fillable = [
        'user_id',
        'user_type',
        'front_photo', 
        'back_photo', 
        'criminal_record_photo',
        'status', 
        'notes',
    ];

}
