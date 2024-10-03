<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $primaryKey = 'sub_category_id';

    protected $fillable = [
        'category_id',
        'name',
        'image',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function services()
    {
        return $this->hasMany(Service::class, 'sub_category_id');
    }
}