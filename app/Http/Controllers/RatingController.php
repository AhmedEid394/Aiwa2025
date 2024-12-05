<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:service_providers,provider_id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $rating = Rating::create($request->all());

        return response()->json($rating, 201);
    }

    public function index()
    {
        return Rating::all();
    }

    /**
     * Get the average rating for a specific provider.
     *
     * @param  int  $provider_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function averageRating($provider_id)
    {
        $averageRating = Rating::where('provider_id', $provider_id)
            ->avg('rating');

        if (is_null($averageRating)) {
            return response()->json([
                'message' => 'No ratings found for this provider.',
                'average_rating' => 1
            ]);
        }

        return response()->json([
            'provider_id' => $provider_id,
            'average_rating' => round($averageRating, 2)
        ]);
    }
}
