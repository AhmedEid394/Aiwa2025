<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Services\CloudImageService;
use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class ServiceProviderController extends Controller
{
    protected $cloudImageService;

    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }

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
                'profile_photo' => 'nullable|file|image|max:2048',
                'sub_category_id' => 'nullable|exists:sub_categories,sub_category_id',
                'maxDistance' => 'nullable|integer',
                'tax_record' => 'nullable|string',
                'company_name' => 'nullable|string',
                'id_number' => 'nullable|string',
                'passport_number' => 'nullable|string',
                'password' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed during registration', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            // Upload profile photo to Cloudinary
            if ($request->hasFile('profile_photo')) {
                $uploadResult = $this->cloudImageService->upload($request->file('profile_photo')->getRealPath());
                $validatedData['profile_photo'] = $uploadResult['secure_url']; // Save the Cloudinary URL
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
                'wallet' => $wallet,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error during service provider registration', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred during registration. Please try again.'], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed during login', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        try {
            $serviceProvider = ServiceProvider::where('phone', $request->input('phone'))->first();

            if ($serviceProvider && Hash::check($request->input('password'), $serviceProvider->password)) {
                $token = $serviceProvider->CreateToken('auth_token')->plainTextToken;

                return response()->json(['ServiceProvider' => $serviceProvider, 'Token' => $token], 201);
            } else {
                return response()->json(['error' => 'The provided credentials are incorrect.'], 401);
            }
        } catch (\Exception $e) {
            Log::error('Error during login', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred during login. Please try again.'], 500);
        }
    }

    public function logout()
    {
        try {
            $user = auth()->user();

            if ($user) {
                // Expire all tokens for the user
                $user->tokens->each(function (PersonalAccessToken $token) {
                    $token->expires_at = now();
                    $token->save();
                });

                return response()->json(['message' => 'Successfully logged out and all tokens expired'], 201);
            }

            return response()->json(['message' => 'No authenticated user found'], 401);
        } catch (\Exception $e) {
            Log::error('Error during logout', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred during logout. Please try again.'], 500);
        }
    }

    public function show()
    {
        try {
            $provider = ServiceProvider::find(auth()->user()->provider_id);
            if (!$provider) {
                return response()->json(['error' => 'Service provider not found'], 404);
            }
            return response()->json($provider);
        } catch (\Exception $e) {
            Log::error('Error fetching service provider details', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred while fetching the service provider details.'], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            Log::info('Updating service provider details', ['request' => $request->all()]);
            $provider = ServiceProvider::find(auth()->user()->provider_id);
            if (!$provider) {
                return response()->json(['error' => 'Service provider not found'], 404);
            }

            $validatedData = $request->validate([
                'f_name' => 'sometimes|string|max:255',
                'l_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:service_providers,email,' . $provider->provider_id . ',provider_id',
                'phone' => 'sometimes|string|unique:service_providers,phone,' . $provider->provider_id . ',provider_id',
                'provider_type' => 'sometimes|in:freelance,corporate',
                'birthday' => 'sometimes|date',
                'nationality' => 'sometimes|in:egyptian,foreigner',
                'gender' => 'sometimes|in:male,female',
                'profile_photo' => 'sometimes|nullable',
                'sub_category_id' => 'sometimes|exists:sub_categories,sub_category_id',
                'maxDistance' => 'sometimes|nullable|integer',
                'tax_record' => 'nullable|string',
                'company_name' => 'nullable|string',
                'id_number' => 'nullable|string',
                'passport_number' => 'nullable|string',
            ]);

            // Upload profile photo to Cloudinary if provided
            if ($request->hasFile('profile_photo')) {
                Log::info('Uploading profile photo to Cloudinary');
                $path = $request->file('profile_photo')->getRealPath();
                if (!$path) {
                    return response()->json(['error' => 'Invalid profile photo provided'], 400);
                }
                $uploadResult = $this->cloudImageService->upload($path);
                $validatedData['profile_photo'] = $uploadResult['secure_url']; // Update the Cloudinary URL
            }

            $provider->update($validatedData);

            return response()->json($provider, 200);
        } catch (\Exception $e) {
            Log::error('Error during service provider update', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'An error occurred during the update. Please try again.',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function destroy()
    {
        try {
            $provider = ServiceProvider::find(auth()->user()->provider_id);
            if (!$provider) {
                return response()->json(['error' => 'Service provider not found'], 404);
            }
            $provider->delete();
            return response()->json(null, 201);
        } catch (\Exception $e) {
            Log::error('Error during service provider deletion', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred during deletion. Please try again.'], 500);
        }
    }

    public function index(Request $request)
    {
        $providers = ServiceProvider::latest()->get();
        return response()->json($providers);
    }

    public function getProvider(Request $request, $id)
    {
        $provider = ServiceProvider::find($id);
        if (!$provider) {
            return response()->json(['error' => 'Service provider not found'], 404);
        }
        return response()->json($provider);
    }

    public function updateProvider(Request $request, $id)
    {
        try {
            Log::info('Updating service provider details', ['request' => $request->all()]);
            $provider = ServiceProvider::find($id);
            if (!$provider) {
                return response()->json(['error' => 'Service provider not found'], 404);
            }

            $validatedData = $request->validate([
                'f_name' => 'sometimes|string|max:255',
                'l_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:service_providers,email,' . $provider->provider_id . ',provider_id',
                'phone' => 'sometimes|string|unique:service_providers,phone,' . $provider->provider_id . ',provider_id',
                'provider_type' => 'sometimes|in:freelance,corporate',
                'birthday' => 'sometimes|date',
                'nationality' => 'sometimes|in:egyptian,foreigner',
                'gender' => 'sometimes|in:male,female',
                'profile_photo' => 'sometimes|nullable',
                'sub_category_id' => 'sometimes|exists:sub_categories,sub_category_id',
                'maxDistance' => 'sometimes|nullable|integer',
                'tax_record' => 'nullable|string',
                'company_name' => 'nullable|string',
                'id_number' => 'nullable|string',
                'passport_number' => 'nullable|string',
            ]);

            // Upload profile photo to Cloudinary if provided
            if ($request->hasFile('profile_photo')) {
                Log::info('Uploading profile photo to Cloudinary');
                $path = $request->file('profile_photo')->getRealPath();
                if (!$path) {
                    return response()->json(['error' => 'Invalid profile photo provided'], 400);
                }
                $uploadResult = $this->cloudImageService->upload($path);
                $validatedData['profile_photo'] = $uploadResult['secure_url']; // Update the Cloudinary URL
            }

            $provider->update($validatedData);

            return response()->json($provider, 200);
        } catch (\Exception $e) {
            Log::error('Error during service provider update', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'An error occurred during the update. Please try again.',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
