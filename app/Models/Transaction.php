<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Transaction extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'user_id',
        'service_id',
        'booking_id',
        'amount',
        'status',
        'transaction_reference',
    ];

    public function Service()
    {
        return $this->hasMany(Service::class, 'service_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'booking_id');
    }
}
