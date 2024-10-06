<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,user_id',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'image' => 'nullable|string',
                'type' => 'required|string',
                'is_read' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $notification = Notification::create($validatedData);

        return response()->json($notification, 201);
    }

    public function show($id)
    {
        $notification = Notification::with('user')->find($id);
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        return response()->json($notification, 201);
    }

    public function destroy($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        $notification->delete();
        return response()->json(null, 201);
    }

    public function index()
    {
        $userId = auth()->user()->user_id;
        if ($userId) {
            $notifications = Notification::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(15);
            $this->markAllAsRead();
        }
        return response()->json($notifications, 201);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        $notification->update(['is_read' => true]);
        return response()->json($notification, 201);
    }

    public function markAllAsRead()
    {
        $userId = auth()->user()->user_id;
        Notification::where('user_id', $userId)->update(['is_read' => true]);
        return response()->json(['message' => 'All notifications marked as read'], 201);
    }
}
