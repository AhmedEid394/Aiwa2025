<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider; // Make sure to create this model
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    // Display a listing of service providers
    public function index()
    {
        $providers = ServiceProvider::all();
        return response()->json($providers);
    }

    // Store a newly created service provider
    public function register(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'f_name' => 'required|string|max:255',
            'l_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:service_providers',
            'phone' => 'required|string|unique:service_providers',
            'provider_type' => 'required|in:freelance,corporate',
            'date_of_birth' => 'required|date',
            'nationality' => 'required|in:Egyptian,Foreigner',
            'gender' => 'required|in:Male,Female',
            'tax_record' => 'nullable|string',
            'company_name' => 'nullable|string',
            'id_number' => 'nullable|string',
            'passport_number' => 'nullable|string',
            'password' => 'required|string|min:8',
        ]);
    
        // Create the service provider
        $provider = ServiceProvider::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'provider_type' => $request->provider_type,
            'date_of_birth' => $request->date_of_birth,
            'nationality' => $request->nationality,
            'gender' => $request->gender,
            'tax_record' => $request->tax_record,
            'company_name' => $request->company_name,
            'id_number' => $request->id_number,
            'passport_number' => $request->passport_number,
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
      if (!$request->user()) {
        return response()->json(['message' => 'No user found'], 404);
    }
    
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // Display a specific service provider
    public function show()
    {
        $provider = ServiceProvider::find(auth()->user()->user_id);
        if (!$provider) {
            return response()->json(['message' => 'Service provider not found'], 404);
        }
        return response()->json($provider);
    }

    // Update a specific service provider
    public function update(Request $request)
    {
        // Find the service provider by ID
        $provider = ServiceProvider::find(auth()->user()->user_id);
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
    

    // Remove a specific service provider
    public function destroy()
    {if (!$provider) {
        return response()->json(['message' => 'Service provider not found'], 404);
    }
        ServiceProvider::destroy(auth()->user()->user_id);
        return response()->json(null, 204);
    }
}
