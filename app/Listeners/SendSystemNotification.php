<?php

namespace App\Listeners;

use App\Models\ServiceProvider;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSystemNotification implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event)
    {
        $data = $event->data;
        $recipientType = $data['recipient_type'] ?? 'all'; // 'all', 'users', 'providers', 'specific'
        $specificUserId = $data['specific_user_id'] ?? null;
        $users = collect(); // Empty collection to hold recipients

        // Define the recipients based on the type
        if ($recipientType === 'all') {
            // Notify all users and providers
            $users = User::all()->merge(ServiceProvider::all());
        } elseif ($recipientType === 'users') {
            // Notify only users
            $users = User::all();
        } elseif ($recipientType === 'providers') {
            // Notify only providers
            $users = ServiceProvider::all();
        } elseif ($recipientType === 'specific' && $specificUserId) {
            // Notify a specific user or provider
            $user = User::find($specificUserId);
            $provider = ServiceProvider::find($specificUserId);

            // Check if the specific ID belongs to a User or ServiceProvider and add to collection
            if ($user) {
                $users = collect([$user]);
            } elseif ($provider) {
                $users = collect([$provider]);
            }
        } else {
            // If no valid recipient type is provided, exit the function
            return;
        }

        // Send notification to the selected users/providers
        foreach ($users as $user) {
            $user->notify(new SystemNotification($data));
        }
    }
}
