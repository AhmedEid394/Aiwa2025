<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CloudImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
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
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|unique:users',
                'gender' => 'required|in:male,female',
                'os' => 'required|string',
                'birthday' => 'required|date',
                'profile_photo' => 'nullable|string',
                'country' => 'nullable|string',
                'maxDistance' => 'nullable|integer',
                'password' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['country'] = null;
        $validatedData['maxDistance'] = 20;

        $user = User::create($validatedData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

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

        $user = User::where('phone', $request->phone)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(['user' => $user, 'token' => $token], 201);
        } else {
            return response()->json(['error' => 'The provided credentials are incorrect.'], 401);
        }
    }

    public function logout()
    {
        $user = auth()->user();

        if ($user) {
            $user->tokens->each(function (PersonalAccessToken $token) {
                $token->expires_at = now();
                $token->save();
            });

            return response()->json(['message' => 'Successfully logged out and all tokens expired'], 201);
        }

        return response()->json(['message' => 'No authenticated user found'], 404);
    }

    public function show()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json(['data' => $user,'success' => true], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function update(Request $request)
    {
        $user = User::find(auth()->user()->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        Log::info('request', ['request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'f_name' => 'sometimes|string|max:255',
                'l_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
                'phone' => 'sometimes|string|unique:users,phone,' . $user->user_id . ',user_id',
                'gender' => 'sometimes|in:male,female',
                'os' => 'sometimes|string',
                'birthday' => 'sometimes|date',
                'profile_photo' => 'nullable',
                'country' => 'nullable|string',
                'maxDistance' => 'nullable|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        // Upload profile photo to Cloudinary if provided
        if ($request->hasFile('profile_photo')) {
            Log::info('Uploading profile photo to Cloudinary');
            $path = $request->file('profile_photo')->getRealPath();
            if (!$path) {
                return response()->json(['error' => 'Invalid profile photo provided'], 400);
            }
            $uploadResult = $this->cloudImageService->upload($path);
            $validatedData['profile_photo'] = $uploadResult['secure_url']; // Update the Cloudinary URL
            Log::info('Profile photo uploaded successfully', ['url' => $validatedData['profile_photo']]);
        }
        $user->update($validatedData);

        return response()->json(['data' => $user,'success' => true],200, ['Content-Type' => 'application/vnd.api+json'], JSON_UNESCAPED_SLASHES);
    }

    public function destroy()
    {
        $user = User::find(auth()->user()->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(null, 201);
    }
}
