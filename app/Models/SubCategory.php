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
        'parent_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    public function services()
    {
        return $this->hasMany(Service::class, 'sub_category_id');
    }
    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }
    public function parent()
    {
        return $this->belongsTo(SubCategory::class, 'parent_id');
    }
}