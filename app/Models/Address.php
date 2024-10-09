<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address_id';
    protected $fillable = ['user_id', 'provider_id', 'city', 'postal_code','street'];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the provider that owns the address.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
