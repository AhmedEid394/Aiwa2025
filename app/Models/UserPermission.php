<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'permission_type_id', 'is_allowed'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permissionType()
    {
        return $this->belongsTo(PermissionType::class);
    }
}
