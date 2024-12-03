<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ServiceProviderController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'f_name' => 'required|string|max:255',
                'l_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:service_providers',
                'phone' => 'required|string|unique:service_providers',
                'provider_type' => 'required|in:freelance,corporate',
                'birthday' => 'required|date',
                'nationality' => 'required|in:egyptian,foreigner',
                'gender' => 'required|in:male,female',
                'profile_photo' => 'nullable|string',
                'sub_category_id' => 'nullable|exists:sub_categories,sub_category_id',
                'tax_record' => 'nullable|string',
                'company_name' => 'nullable|string',
                'id_number' => 'nullable|string',
                'passport_number' => 'nullable|string',
                'password' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Hash the password
        $validatedData['password'] = Hash::make($validatedData['password']);
        
        // Create the service provider
        $provider = ServiceProvider::create($validatedData);

        // Create a wallet with default values
        $wallet = Wallet::create([
            'provider_id' => $provider->provider_id,
            'total_amount' => 0,        
            'available_amount' => 0,      
        ]);

        // Generate an auth token for the service provider
        $token = $provider->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'provider' => $provider,
            'wallet' => $wallet
        ], 201);
    }

    public function login(Request $request)
    {
        try {
            $request->validate( [
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $serviceProvider = ServiceProvider::where('phone', $request->input('phone'))->first();

        if ($serviceProvider && Hash::check($request->input('password'), $serviceProvider->password)) {

            $token = $serviceProvider->CreateToken('auth_token')->plainTextToken;

            return response()->json(['ServiceProvider' => $serviceProvider, 'Token' => $token], 201);
        } else {
            return response()->json(['error' => 'The provided credentials are incorrect.'], 401);
        }
    }


    public function logout()
    {
        $user = auth()->user();

        if ($user) {
            // Expire all tokens for the user by setting their expiration to now
            $user->tokens->each(function (PersonalAccessToken $token) {
                $token->expires_at = now();
                $token->save();
            });

            return response()->json(['message' => 'Successfully logged out and all tokens expired'], 201);
        }

        return response()->json(['message' => 'No authenticated user found'], 401);
    }


    // Display a specific service provider
    public function show()
    {
        $provider = ServiceProvider::find(auth()->user()->provider_id);
        if (!$provider) {
            return response()->json(['error' => 'Service provider not found'], 404);
        }
        return response()->json($provider);
    }


    // Update a specific service provider
    public function update(Request $request)
    {
        $provider = ServiceProvider::find(auth()->user()->provider_id);
        if (!$provider) {
            return response()->json(['error' => 'Service provider not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'f_name' => 'sometimes|string|max:255',
                'l_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:service_providers,email,' . $provider->provider_id. ',provider_id',
                'phone' => 'sometimes|string|unique:service_providers,phone,' . $provider->provider_id. ',provider_id',
                'provider_type' => 'sometimes|in:freelance,corporate',
                'birthday' => 'sometimes|date',
                'nationality' => 'sometimes|in:egyptian,foreigner',
                'gender' => 'sometimes|in:male,female',
                'profile_photo' => 'nullable|string',
                'sub_category_id' => 'sometimes|exists:sub_categories,sub_category_id',
                'tax_record' => 'nullable|string',
                'company_name' => 'nullable|string',
                'id_number' => 'nullable|string',
                'passport_number' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $provider->update($validatedData);

        return response()->json($provider,200);
    }

    // Remove a specific service provider
    public function destroy()
    {
        $provider = ServiceProvider::find(auth()->user()->provider_id);
        if (!$provider) {
            return response()->json(['error' => 'Service provider not found'], 404);
        }
        $provider->delete();
        return response()->json(null, 201);
    }
}
