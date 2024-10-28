<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [
        'bank_en',
        'bank_ar',
        'bank_short',
        'logo'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}

