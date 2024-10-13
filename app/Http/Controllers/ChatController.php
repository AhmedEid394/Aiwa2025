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
                'user_id' => 'required|exists:users,user_id', // Ensure user_id is required and exists in the users table
                'provider_id' => 'required|exists:service_providers,provider_id', // Ensure provider_id is required and exists in the service_providers table
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

    public function sendMessage(Request $request, $chatId)
    {
        // Validate incoming request
        $request->validate([
            'message' => 'required|string',
            'file' => 'nullable|file|max:2048',
        ]);
    
        $filePath = null; // Initialize filePath to null
    
        // Check if a file is present
        if ($request->hasFile('file')) {
            // Use any method to store the file, e.g., move() or store()
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName(); // Create a unique filename
            $filePath = 'chat_files/' . $filename; // Define the path
    
            // Move the file to the specified directory
            $file->move(public_path('storage/' . $filePath)); // Move the file
        }
    
        // Get the authenticated user
        $user = $request->user(); 
    
        // Determine sender type based on user instance
        if ($user instanceof ServiceProvider) {
            $senderType = 'Provider';
            $senderId = $user->id;
        } elseif ($user instanceof User) {
            $senderType = 'user';
            $senderId = $user->id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'message' => $request->message,
            'file' => $filePath, // This will be null if no file was uploaded
            'sender_type' => $senderType,
            'sender_id' => $senderId,
        ]);
    
        // Broadcast the message
        broadcast(new \App\Events\MessageSent($message))->toOthers();
    
        // Return response with the created message and file path
        return response()->json([
            'message' => $message,
            'file_path' => $filePath, // Will be null if no file was uploaded
        ], 201);
    }
    
    
    
    
    
    

    public function getMessages($chatId)
    {
        $chat = Chat::find($chatId);
    
        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }
            $messages = Message::where('chat_id', $chatId)->orderBy('created_at')->get();
        return response()->json($messages);
    }
    

}
