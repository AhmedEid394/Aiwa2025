<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'f_name' => 'required|string|max:255',
                'l_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'required|string|unique:users',
                'gender' => 'required|in:Male,Female',
                'os' => 'required|string',
                'birthday' => 'required|date',
                'nationality' => 'required|string',
                'profile_photo' => 'nullable|string',
                'address' => 'nullable|string',
                'password' => 'required|string|min:8',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $validatedData['password'] = Hash::make($validatedData['password']);
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
        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = User::find(auth()->user()->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'f_name' => 'sometimes|string|max:255',
                'l_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->user_id . ',user_id',
                'phone' => 'sometimes|string|unique:users,phone,' . $user->user_id . ',user_id',
                'gender' => 'sometimes|in:Male,Female',
                'os' => 'sometimes|string',
                'birthday' => 'sometimes|date',
                'nationality' => 'sometimes|string',
                'profile_photo' => 'nullable|string',
                'address' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user->update($validatedData);

        return response()->json($user);
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
