<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $primaryKey = 'service_id';

    protected $fillable = [
        'title',
        'sub_category_id',
        'provider_id',
        'description',
        'service_fee',
        'pictures',
        'add_ons',
        'sale_amount',
        'sale_percentage',
        'down_payment',
    ];

    protected $casts = [
        'pictures' => 'array',
        'add_ons' => 'array',
        'service_fee' => 'decimal:2',
        'sale_amount' => 'decimal:2',
        'sale_percentage' => 'decimal:2',
        'down_payment' => 'decimal:2',
    ];

    public function SubCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function Provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'service_id');
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class, 'service_id');
    }

}