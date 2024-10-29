<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'user_type',
        'user_id' ,
        'service_id',
        'add_ons',
        'building_number' ,
        'apartment' ,
        'location_mark' ,
        'latitude',
        'longitude' ,
        'booking_date' ,
        'booking_time' ,
        'service_price' ,
        'total_price' ,
        'promo_code' ,
        'status' ,
    ];

    protected $casts = [
        'add_ons' => 'array',
        'booking_date' => 'date',
        'booking_time' => 'datetime',
        'service_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}