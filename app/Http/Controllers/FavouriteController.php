<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
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
        $userId = auth()->user()->user_id;
        if ($userId) {
            $favourites = Favourite::with('service')->where('user_id', $userId)->paginate(15);
        } else {
            $favourites = Favourite::with(['user', 'service'])->paginate(15);
        }
        return response()->json($favourites, 201);
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
        $user_id = auth()->user()->user_id;

        // Search for an existing favourite
        $favourite = Favourite::where('user_id', $user_id)
            ->where('service_id', $validatedData['service_id'])
            ->first();

        if ($favourite) {
            // If found, delete the favourite
            $favourite->delete();
            return response()->json(['message' => 'Favourite removed'], 201);
        } else {
            // Otherwise, create a new favourite with the given user_id and service_id
            $favourite = Favourite::create([
                'user_id' => $user_id,
                'service_id' => $validatedData['service_id']
            ]);
            return response()->json($favourite, 201);
        }
    }
}
