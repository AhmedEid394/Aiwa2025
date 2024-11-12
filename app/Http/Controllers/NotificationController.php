<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications.
     *
     * @return JsonResponse
     */
    public function index()
    {
        // Get all notifications for the authenticated user
        $notifications = auth()->user()->notifications;

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Get only unread notifications
     *
     * @return JsonResponse
     */
    public function unread()
    {
        $notifications = auth()->user()->unreadNotifications;

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Get unread notifications count
     *
     * @return JsonResponse
     */
    public function unreadCount()
    {
        $count = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Get notifications with count for badge
     *
     * @return JsonResponse
     */
    public function getNotificationsWithCount()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count()
        ]);
    }

    /**
     * Mark notification as read
     *
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead($id)
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => auth()->user()->unreadNotifications()->count() // Return updated count
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }

    /**
     * Mark all notifications as read
     *
     * @return JsonResponse
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'unread_count' => 0
        ]);
    }

    /**
     * Delete a notification
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $notification = DatabaseNotification::find($id);

        if ($notification && $notification->notifiable_id === auth()->id()) {
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
                'unread_count' => auth()->user()->unreadNotifications()->count() // Return updated count
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found'
        ], 404);
    }
}
