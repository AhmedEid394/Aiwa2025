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

        $favourites = Favourite::with('service')->where('user_id', $userId)->get();
        return response()->json(['data' => $favourites, 'success' => true], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function toggle(Request $request)
    {
        try {
            // Validate only the 'service_id' as 'user_id' is now constant
            $validatedData = $request->validate([
                'service_id' => 'required|exists:services,service_id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // Set user_id as the authenticated user's ID
        $user = auth()->user();
        if ($user instanceof ServiceProvider) {
            $userId= $user->provider_id;
        } elseif ($user instanceof User) {
            $userId= $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // Search for an existing favourite
        $favourite = Favourite::where('user_id', $userId)
            ->where('service_id', $validatedData['service_id'])
            ->first();

        if ($favourite) {
            // If found, delete the favourite
            $favourite->delete();
            return response()->json(['message' => 'Favourite removed'], 201);
        } else {
            // Otherwise, create a new favourite with the given user_id and service_id
            $favourite = Favourite::create([
                'user_id' => $userId,
                'service_id' => $validatedData['service_id']
            ]);            return response()->json($favourite, 201);
        }
    }
}
