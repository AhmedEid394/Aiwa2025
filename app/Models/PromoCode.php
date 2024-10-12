<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasFactory;
    protected $primaryKey = 'promo_code_id';
    protected $fillable = [
      'code',
      'percentage',
      'service_provider_id',
      'due_date',
      'service_id',
  ];

  // Define the relationships
  public function serviceProvider()
  {
      return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
  }

  public function service()
  {
      return $this->belongsTo(Service::class, 'service_id');
  }
}
