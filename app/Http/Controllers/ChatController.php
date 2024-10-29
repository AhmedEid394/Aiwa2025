<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Validation\ValidationException;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Storage;


class ChatController extends Controller
{

    public function startChat(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,user_id', 
                'provider_id' => 'required|exists:service_providers,provider_id', 
            ]);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }


        $chat = Chat::firstOrCreate([
            'user_id' => $request->user_id,
            'provider_id' => $request->provider_id,
        ]);

        return response()->json($chat, 201);
    }

    public function checkChatExists(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'provider_id' => 'required|exists:service_providers,provider_id',
        ]);

        $chat = Chat::where('user_id', $request->user_id)
            ->where('provider_id', $request->provider_id)
            ->first();

        return response()->json(['exists' => $chat ? true : false], 200);
    }

    public function sendMessage(Request $request, $chatId)
    {
        // Validate incoming request
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:2048',
        ]);
    
        $fileEncoded = null; // Initialize fileEncoded to null
    
        // Check if a file is present
        if ($request->hasFile('file')) {
            // Get the uploaded file
            $file = $request->file('file');
    
            // Read the file's contents and encode it to Base64
            $fileContents = file_get_contents($file->getRealPath());
            $fileEncoded = base64_encode($fileContents);
        }
    
        // Get the authenticated user
        $user = auth()->user();
    
        // Determine sender type based on user instance
        if ($user instanceof ServiceProvider) {
            $senderType = 'Provider';
            $senderId = $user->provider_id; // Use `provider_id` for providers
        } elseif ($user instanceof User) {
            $senderType = 'user';
            $senderId = $user->user_id; // Use `user_id` for users
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'message' => $request->message,
            'file' => $fileEncoded, 
            'sender_type' => $senderType,
            'sender_id' => $senderId,
        ]);
    
        // Broadcast the message
        broadcast(new \App\Events\MessageSent($message))->toOthers();
    
        // Return response with the created message and file
        return response()->json([
            'message' => $message,
            'file' => $fileEncoded, // Return the encoded file (if uploaded)
        ], 201);
    }
    

    public function getMessages($chatId)
    {
        $chat = Chat::find($chatId);
    
        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
    
        $messages = Message::where('chat_id', $chatId)->orderBy('created_at')->get();
    
        foreach ($messages as $message) {
            // Only decode the file if it exists
            if ($message->file) {
                $message->file = 'data:application/octet-stream;base64,' . $message->file; // Adjust MIME type as needed
            }
        }
    
        return response()->json(['messages' => $messages], 200);
    }
    
    
    
    public function deleteMessage(Request $request, $messageId)
    {
        // Find the message by its ID
        $message = Message::find($messageId);
    
        // Check if the message exists
        if (!$message) {
            return response()->json(['error' => 'Message not found'], 404);
        }
    
        // Get the authenticated user (or service provider)
        $user = $request->user(); 
        // Determine if the authenticated user is allowed to delete the message
        $isAuthorized = 
            ($message->sender_type === 'user' && $user instanceof User && $message->sender_id === $user->user_id) ||
            ($message->sender_type === 'Provider' && $user instanceof ServiceProvider && $message->sender_id === $user->provider_id);
    
        // Check if the user is authorized to delete the message
        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized to delete this message'], 403);
        }
    
        // Attempt to delete the message file if it exists
        if ($message->file && Storage::disk('public')->exists($message->file)) {
            Storage::disk('public')->delete($message->file);
        }
    
        // Delete the message
        $message->delete();
    
        return response()->json(['message' => 'Message deleted successfully'], 200);
    }
    


}
