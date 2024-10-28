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
        'email',
        'password',
        'phone',
        'gender',
        'os',
        'birthday',
        'profile_photo',
        'country',
        'maxDistance',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];


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
  
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
    public function papers()
    {
        return $this->hasMany(Paper::class, 'user_id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'user_id');
    }
  }