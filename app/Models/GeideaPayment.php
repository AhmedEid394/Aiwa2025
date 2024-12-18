<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeideaPayment extends Model
{
    protected $table = 'geidea_payments';

    protected $fillable = [
        'session_id',
        'service_amount',
        'bm_fees',
        'geidea_fees',
        'aiwa_fees',
        'tax_14_percent',
        'total_amount',
        'cash_in',
        'reservation_id',
        'user_send_id',
        'user_send_type',
        'user_receive_id',
        'payment_intent_id',
        'merchant_reference_id'
    ];

}
