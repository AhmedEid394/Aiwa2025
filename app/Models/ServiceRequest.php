<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'user_id',
        'user_type',
        'provider_id',
        'sub_category_id',
        'title',
        'description',
        'date_of_done',
        'location',
        'expected_cost',
        'pictures',
        'status',
        'building_number',
        'apartment',
        'location_mark',
    ];

    protected $casts = [
        'date_of_done' => 'date',
        'expected_cost' => 'decimal:2',
        'pictures' => 'array',
    ];

    public function user()
    {
        if ($this->user_type === 'user') {
            return User::where('user_id', $this->user_id)->first();
        }
        else {
            return ServiceProvider::where('provider_id', $this->user_id)->first();
        }
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id', 'sub_category_id');
    }
}
