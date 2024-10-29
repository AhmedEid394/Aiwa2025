<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // First try to find a regular user
        $user = User::where('phone', $request->phone)->first();
        
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'type' => 'user'
            ], 201);
        }

        // If no user found or password doesn't match, try service provider
        $serviceProvider = ServiceProvider::where('phone', $request->phone)->first();
        
        if ($serviceProvider && Hash::check($request->password, $serviceProvider->password)) {
            $token = $serviceProvider->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $serviceProvider,
                'token' => $token,
                'type' => 'provider'
            ], 201);
        }

        // If neither found or passwords don't match
        return response()->json([
            'error' => 'The provided credentials are incorrect.'
        ], 401);
    }
}
