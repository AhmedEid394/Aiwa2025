<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
public function update(Request $request)
{
$validated = $request->validate([
'fcm_token' => 'required|string'
]);

FcmToken::updateOrCreate(
[
'user_id' => $request->user()->id,
'token' => $validated['fcm_token']
]
);

return response()->json(['message' => 'FCM token updated successfully']);
}
}

