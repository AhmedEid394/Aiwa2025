<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';
    protected $primaryKey = 'address_id';
    protected $fillable = [
        'user_id',
        'provider_id',
        'user_type',
        'city',
        'street',
        'building',
        'apartment',
        'location_mark',
        'latitude',
        'longitude',
    ];

    /**
     * Get the user that owns the address.
     */

}
