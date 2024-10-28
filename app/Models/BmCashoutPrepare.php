<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BmCashoutPrepare extends Model
{
    protected $table = 'bm_cashout_prepare';
    
    protected $primaryKey = 'bm_cashout_id';

    protected $fillable = [
        'message_id',
        'transaction_id',
        'debtor_account',
        'currency',
        'transaction_amount',
        'transaction_amount_aiwa_fees',
        'final_transaction_amount',
        'creditor_name',
        'creditor_account_number',
        'creditor_bank',
        'corporate_code',
        'category_code',
        'transaction_date_time',
        'creditor_id',
        'signature',
        'prepared_flag',
        'response_code',
        'response_description'
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'transaction_amount_aiwa_fees' => 'decimal:2',
        'final_transaction_amount' => 'decimal:2',
        'prepared_flag' => 'boolean',
        'transaction_date_time' => 'datetime',
        'created_at' => 'datetime'
    ];
    
    public function status()
    {
        return $this->hasOne(BmCashoutStatus::class, 'bm_cashout_id');
    }
}
