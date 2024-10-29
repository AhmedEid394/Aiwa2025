<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BmCashoutStatus extends Model
{
    protected $table = 'bm_cashout_status';
    
    protected $primaryKey = 'bm_cashout_status_id';

    protected $fillable = [
        'bm_cashout_id',
        'message_id',
        'transaction_id',
        'transaction_status_code',
        'transaction_status_description'
    ];

    public function prepare()
    {
        return $this->belongsTo(BmCashoutPrepare::class, 'bm_cashout_id');
    }
}
