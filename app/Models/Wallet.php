<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
protected $primaryKey = 'wallet_id';
    protected $fillable = [
        'provider_id',
        'total_amount',
        'available_amount',
    ];

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id', 'provider_id');
    }
}
