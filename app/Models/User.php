<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'f_name',
        'l_name',
        'role',
        'email',
        'password',
        'phone',
        'gender',
        'os',
        'birthday',
        'nationality',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'user_id');
    }
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
    public function papers()
    {
        return $this->hasMany(Paper::class, 'user_id');
    }
  }