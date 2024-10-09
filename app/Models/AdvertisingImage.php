<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvertisingImage extends Model
{
    use HasFactory;
 protected $primaryKey = 'advertising_image_id';
    protected $fillable = [
        'image_path',
        'status',
    ];
}
