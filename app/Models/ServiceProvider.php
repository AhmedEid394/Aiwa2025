<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ServiceProvider extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'provider_id';

    protected $fillable = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'provider_type',
        'date_of_birth',
        'nationality',
        'gender',
        'tax_record',
        'company_name',
        'id_number',
        'passport_number',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];
   
    public function Service()
    {
        return $this->hasMany(Service::class, 'provider_id');
    }
}