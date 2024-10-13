<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
  public function startChat(Request $request)
    {
        $chat = Chat::firstOrCreate([
            'user_id' => $request->user_id,
            'provider_id' => $request->provider_id,
        ]);

        return response()->json($chat, 201);
    }

    public function sendMessage(Request $request, $chatId)
    {
        try {
            $request->validate([
                'message' => 'nullable|required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'sender_type' => 'required|string|in:user,provider',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat_images', 'public');  // Store in 'storage/app/public'
            Log::info('Image uploaded to: ' . $imagePath);  // Log the upload path
        } else {
            Log::info('No image uploaded');
        }

        $message = Message::create([
            'chat_id' => $chatId,
            'message' => $request->message,
            'image' => $imagePath,
            'sender_type' => $request->sender_type, // 'user' or 'provider'
        ]);
        $imageUrl = $imagePath ? Storage::url($imagePath) : null;
        Log::info('Image URL: ' . $imageUrl);  // Log the URL being sent in response
    
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function getMessages($chatId)
    {
        $messages = Message::where('chat_id', $chatId)->orderBy('created_at')->get();
        return response()->json($messages);
    }

}
