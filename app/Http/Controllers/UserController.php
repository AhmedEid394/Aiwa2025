<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Display a listing of users
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Store a newly created user
    public function register(Request $request)
    {
        // Validate the incoming request
        $request->validate([
          'f_name' => 'required|string|max:255',
          'l_name' => 'required|string|max:255',
          'email' => 'required|string|email|max:255|unique:service_providers',
          'phone' => 'required|string|unique:service_providers',
          'gender' => 'required|in:Male,Female',
          'os' => 'required|string|max:255', // Operating system
          'birthday' => 'required|date', // Changed from date_of_birth to birthday
          'nationality' => 'required|string|max:255', // Nationality
          'profile_photo' => 'nullable|string|max:255', // Profile photo (optional)
          'address' => 'required|string|max:255', // Address
          'password' => 'required|string|min:8',
      ]);
      
      // Create the service provider
      $provider = ServiceProvider::create([
          'f_name' => $request->f_name,
          'l_name' => $request->l_name,
          'email' => $request->email,
          'phone' => $request->phone,
          'gender' => $request->gender,
          'os' => $request->os, // Operating system
          'birthday' => $request->birthday, // Changed from date_of_birth to birthday
          'nationality' => $request->nationality,
          'profile_photo' => $request->profile_photo, // Profile photo (optional)
          'address' => $request->address, // Address
          'password' => Hash::make($request->password), // Hash the password
      ]);
      
    
        // Create a token for the newly registered provider
        $token = $provider->createToken('auth_token')->plainTextToken;
    
        // Return the provider and token in the response
        return response()->json(['token' => $token, 'provider' => $provider], 201);
    }
    

    public function login(Request $request)
    {
        $credentials = $request->only('phone', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(['token' => $token, 'user' => $user]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // Display a specific user
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    // Update a specific user
    public function update(Request $request)
    {
        // Find the service provider by ID
        $provider = User::find(auth()->user()->user_id);
        if (!$provider) {
            return response()->json(['message' => 'Service provider not found'], 404);
        }
    
        // Filter the request data to remove any null or empty values
        $filteredData = array_filter($request->all(), function ($value) {
            return !is_null($value) && $value !== '';
        });
    
        // Update the provider with the filtered data
        $provider->update($filteredData);
    
        // Return the updated provider
        return response()->json($provider);
    }
    

    // Remove a specific user
    public function destroy()
{
    $user = User::find(auth()->user()->user_id);
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }   
       $user->delete();
       return response()->json(['message' => 'The user has been deleted'], 204);
}

}
