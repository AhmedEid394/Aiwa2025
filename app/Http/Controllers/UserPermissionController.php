<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\UserPermission;

class UserPermissionController extends Controller
{
  public function store(Request $request)
  {
      $request->validate([
          'permission_type_id' => 'required|exists:permission_types,permission_type_id', // Ensure permission type exists
      ]);
  
      // Ensure the user is authenticated
      $user = auth()->user();
  
      if ($user instanceof ServiceProvider) {
          $userType = 'Provider';
          $user_id = $user->provider_id; // Use `provider_id` for providers
      } elseif ($user instanceof User) {
          $userType = 'user';
          $user_id = $user->user_id; // Use `user_id` for users
      } else {
          return response()->json(['error' => 'Unauthorized'], 401);
      }
  
      // Find the existing permission or create a new one with default is_allowed = false
      $permission = UserPermission::updateOrCreate(
          [
              'user_type' => $userType,
              'permission_type_id' => $request->permission_type_id,
              'user_id' => $user_id,
          ]
      );
  
      // Toggle the is_allowed value
      $permission->is_allowed = !$permission->is_allowed;
      
      // Save the permission record
      $permission->save();
  
      // Return the updated permission as a JSON response
      return response()->json($permission, 200);
  }
  
  public function index()
  {
      $user = auth()->user();
  
      if ($user instanceof ServiceProvider) {
          $userType = 'Provider';
          $user_id = $user->provider_id; // Use `provider_id` for providers
      } elseif ($user instanceof User) {
          $userType = 'user';
          $user_id = $user->user_id; // Use `user_id` for users
      } else {
          return response()->json(['error' => 'Unauthorized'], 401);
      }
  
      $permissions = UserPermission::where('user_type', $userType)
          ->where('user_id', $user_id)
          ->get();
  
      return response()->json($permissions, 200);
  }
}
