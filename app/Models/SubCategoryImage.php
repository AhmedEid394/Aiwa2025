<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoryImage extends Model
{
    use HasFactory;
    protected $primaryKey = 'subcategory_image_id';

   protected $fillable = [
    'subcategory_id', 
    'image_path',    
    'alt_text',       
];

// Define the relationship with the SubCategory model
public function subcategory()
{
    return $this->belongsTo(SubCategory::class, 'subcategory_id');
}
}
