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
            $validatedData = $request->validate([
                'user_id' => auth()->user()->user_id,
                'service_id' => 'required|exists:services,service_id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $favourite = Favourite::where('user_id', $validatedData['user_id'])
            ->where('service_id', $validatedData['service_id'])
            ->first();

        if ($favourite) {
            $favourite->delete();
            return response()->json(['message' => 'Favourite removed'], 201);
        } else {
            $favourite = Favourite::create($validatedData);
            return response()->json($favourite, 201);
        }
    }
}
