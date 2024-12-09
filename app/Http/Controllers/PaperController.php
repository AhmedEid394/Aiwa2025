<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Validation\ValidationException;


class PaperController extends Controller
{
    public function uploadPapers(Request $request)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'front_photo' => 'nullable|string',
                'back_photo' => 'nullable|string',
                'criminal_record_photo' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            $userType = 'Provider';
            $userId = $user->provider_id;
        } elseif ($user instanceof User) {
            $userType = 'user';
            $userId = $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Add user_type and user_id to the validated data
        $validatedData['user_type'] = $userType;
        $validatedData['user_id'] = $userId;

        // Update or create paper record based on user_id and user_type
        $paper = Paper::updateOrCreate(
            [
                'user_id' => $userId,
                'user_type' => $userType,
            ],
            $validatedData // Update with the validated data
        );

        return response()->json([
            'data' => $paper,
            'success' => true,
        ], 200, ['Content-Type' => 'application/vnd.api+json'], JSON_UNESCAPED_SLASHES);
    }


    public function getPapers()
    {
        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            $user_type = 'Provider';
            $user_id = $user->provider_id;
        } elseif ($user instanceof User) {
            $user_type = 'user';
            $user_id = $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $papers = Paper::where('user_id', $user_id)
            ->where('user_type', $user_type)
            ->first();

        return response()->json([
            'data' => $papers,
            'success' => true
        ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function checkVerification()
    {
        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            // Check if provider has submitted and approved papers
            $verificationStatus = Paper::where('user_id', $user->provider_id)
                ->where('user_type', 'Provider')
                ->where('status', 'accepted')
                ->exists();

            return response()->json([
                'is_verified' => $verificationStatus,
                'success' => true
            ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
        } elseif ($user instanceof User) {
            // If you want to implement user verification logic
            // For now, returning false
            return response()->json([
                'is_verified' => false,
                'success' => true
            ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
