<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date_of_done',
        'location',
        'expected_cost',
        'pictures',
        'status',
    ];

    protected $casts = [
        'date_of_done' => 'date',
        'expected_cost' => 'decimal:2',
        'pictures' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}