<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
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

    public function index()
    {
        $user = auth()->user();

        $providerId=null;

        $userId=null;

        // Determine sender type based on user instance
        if ($user instanceof ServiceProvider) {
            $providerId = $user->provider_id; // Use `provider_id` for providers
        } elseif ($user instanceof User) {
            $userId = $user->user_id; // Use `user_id` for users
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get all chats for the user with their latest messages
        $chats = Chat::where('user_id', $userId)
            ->with(['Provider']) // Eager load service provider details
            ->withCount('messages') // Get message count for each chat
            ->addSelect([
                'latest_message_id' => Message::select('message_id')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_text' => Message::select('message')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_created_at' => Message::select('created_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_sender_type' => Message::select('sender_type')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_sender_id' => Message::select('sender_id')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_read_at' => Message::select('read_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1)
            )
            ->get();

        // Get all chats for the provider with their latest messages
        $chats = Chat::where('provider_id', $providerId)
            ->with(['user']) // Eager load user details
            ->withCount('messages') // Get message count for each chat
            ->addSelect([
                'latest_message_id' => Message::select('message_id')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_text' => Message::select('message')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_created_at' => Message::select('created_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_sender_type' => Message::select('sender_type')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_sender_id' => Message::select('sender_id')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
                'latest_message_read_at' => Message::select('read_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1),
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('chat_id', 'chats.chat_id')
                    ->latest()
                    ->take(1)
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' =>(!$providerId)?( $chats->map(function ($chat) {
                return [
                    'chat_id' => $chat->chat_id,
                    'provider_id' => $chat->provider_id,
                    'provider_name' =>  $chat->provider->f_name . ' ' . $chat->provider->l_name ?? null,
                    'provider_picture' =>  $chat->provider->profile_photo ?? null,
                    'messages_count' => $chat->messages_count,
                    'provider' => $chat->provider,
                    'latest_message' => $chat->latest_message_id ? [
                        'message_id' => $chat->latest_message_id,
                        'message' => $chat->latest_message_text,
                        'created_at' => $chat->latest_message_created_at,
                        'sender_type' => $chat->latest_message_sender_type,
                        'sender_id' => $chat->latest_message_sender_id,
                        'read_at'=>$chat->latest_message_read_at,
                    ] : null,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                ];
            })):($chats->map(function ($chat) {
                return [
                    'chat_id' => $chat->chat_id,
                    'user_id' => $chat->user->user_id,
                    'user_name' =>  $chat->user->f_name . ' ' . $chat->user->l_name ?? null,
                    'user_picture' =>  $chat->user->profile_photo ?? null,
                    'messages_count' => $chat->messages_count,
                    'user' => $chat->user,
                    'provider' => $chat->provider,
                    'latest_message' => $chat->latest_message_id ? [
                        'message_id' => $chat->latest_message_id,
                        'message' => $chat->latest_message_text,
                        'created_at' => $chat->latest_message_created_at,
                        'sender_type' => $chat->latest_message_sender_type,
                        'sender_id' => $chat->latest_message_sender_id,
                        'read_at'=>$chat->latest_message_read_at,
                    ] : null,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                ];
            }))
        ], 201);
    }

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
            'provider_id' => 'required|exists:service_providers,provider_id',
        ]);

        ($request->user_id)?($chat = Chat::where('provider_id', auth()->user()->provider_id)->where('user_id', $request->user_id)->first())
            :($chat = Chat::where('user_id', auth()->user()->user_id)->where('provider_id', $request->provider_id)->first());

        return response()->json(['exists' => $chat ? true : false], 200);
    }

    public function sendMessage(Request $request)
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
            $senderType = 'provider';
            $senderId = $user->provider_id; // Use `provider_id` for providers
        } elseif ($user instanceof User) {
            $senderType = 'user';
            $senderId = $user->user_id; // Use `user_id` for users
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Create the message
        $message = Message::create([
            'chat_id' => $request->chatId,
            'message' => $request->message,
            'file' => $fileEncoded,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
        ]);

        event(new MessageSent($message));

        // Return response with the created message and file
        return response()->json([
            'message' => $message,
            'file' => $fileEncoded, // Return the encoded file (if uploaded)
        ], 201);
    }


    public function getMessages(Request $request)
    {
        $chat = Chat::find($request->chatId);

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $messages = Message::where('chat_id', $request->chatId)->orderBy('created_at')->get();

        return response()->json($messages, 200);
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
