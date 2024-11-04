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
        'birthday',
        'nationality',
        'gender',
        'profile_photo',
        'sub_category_id',
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

    public function SubCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    public function promoCodes(){
        return $this->hasMany(PromoCode::class, 'provider_id');
    }
    public function papers()
    {
        return $this->hasMany(Paper::class, 'provider_id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'provider_id');
    }
    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'provider_id');
    }
    
}