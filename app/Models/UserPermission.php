<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;
    protected $primaryKey = 'user_permission_id';
    protected $fillable = ['permission_type_id','user_type','user_id','is_allowed'];
    public function permissionType()
    {
        return $this->belongsTo(PermissionType::class, 'permission_type_id');
    }
}
