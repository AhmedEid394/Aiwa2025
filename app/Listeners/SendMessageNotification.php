<?php

namespace App\Listeners;

use App\Models\Chat;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Notifications\MassageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMessageNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\MessageSent  $event
     * @return void
     */
    public function handle(object $event): void
    {
        $message = $event->message;

        // First, retrieve the chat
        $chat = Chat::where('chat_id', $message->chat_id)->first();
        
        // Compare sender_type using strict string comparison
        if ($message->sender_type === 'user') {
            // If sender is user, recipient is provider
            $recipient = ServiceProvider::where('provider_id', $chat->provider_id)->first();
            if ($recipient) {
                $recipient->notify(new MassageNotification($message));
            }
        } elseif ($message->sender_type === 'provider') {
            // If sender is provider, recipient is user
            $recipient = User::where('user_id', $chat->user_id)->first();
            if ($recipient) {
                $recipient->notify(new MassageNotification($message));
            }
        }
    }
}
