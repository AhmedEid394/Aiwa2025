<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string'
        ]);
        $user = $request->user();
        $user_id=null;
        $user_type=null;
        if ($user instanceof ServiceProvider) {
            $user_id= $user->provider_id;
            $user_type='Provider';
        } elseif ($user instanceof User) {
            $user_id= $user->user_id;
            $user_type='user';
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        FcmToken::updateOrCreate(
            [
                'token' => $validated['fcm_token']
            ],
            [
                'user_id' =>$user_id,
                'user_type' => $user_type,
                'token' => $validated['fcm_token']
            ]
        );

        return response()->json(['message' => 'FCM token updated successfully']);
    }
}

