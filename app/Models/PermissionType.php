<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }
}
