<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FavouriteController extends Controller
{
    public function show($id)
    {
        $favourite = Favourite::with(['user', 'service'])->find($id);
        if (!$favourite) {
            return response()->json(['error' => 'Favourite not found'], 404);
        }
        return response()->json($favourite, 201);
    }

    public function index()
    {
        $user = auth()->user();
        if ($user instanceof ServiceProvider) {
            $userId= $user->provider_id;
        } elseif ($user instanceof User) {
            $userId= $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $favourites = Favourite::with(['service' => function($query) {
            $query->with('Provider');
        }])
            ->where('user_id', $userId)
            ->get();

        return response()->json(['data' => $favourites, 'success' => true], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function toggle(Request $request)
    {
        try {
            // Validate the 'service_id'
            $validatedData = $request->validate([
                'service_id' => 'required|exists:services,service_id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Determine user type and ID
        $user = auth()->user();
        if ($user instanceof ServiceProvider) {
            $userId = $user->provider_id;
            $userType = 'Provider';
        } elseif ($user instanceof User) {
            $userId = $user->user_id;
            $userType = 'user';
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Search for an existing favourite with user_type
        $favourite = Favourite::where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('service_id', $validatedData['service_id'])
            ->first();

        if ($favourite) {
            // If found, delete the favourite
            $favourite->delete();
            return response()->json(['message' => 'Favourite removed'], 200);
        } else {
            // Otherwise, create a new favourite with user_id, user_type, and service_id
            $favourite = Favourite::create([
                'user_id' => $userId,
                'user_type' => $userType,
                'service_id' => $validatedData['service_id']
            ]);
            return response()->json($favourite, 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
        }
    }


}
