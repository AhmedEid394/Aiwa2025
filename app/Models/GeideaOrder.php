<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeideaOrder extends Model
{
    protected $table = 'geidea_orders';

    protected $fillable = [
        'session_id',
        'order_id',
        'total_amount',
        'currency',
        'language',
        'detailed_status',
        'status',
        'three_d_secure_id',
        'merchant_id',
        'merchant_public_key',
        'merchant_reference_id',
        'order_source',
        'created_date',
        'updated_date',
        'response_code',
        'detailed_response_code',
        'payment_method_type',
        'payment_method_brand',
        'payment_method_cardholder_name',
        'reservation_id'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}