<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $message;
    public function __construct( $message )
    {
      $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->chat_id);
    }
    public function broadcastWith()
    {
        return [
            'chat_id' => $this->message->chat_id,
            'message' => $this->message->message,
            'image' => $this->message->image,
            'sender_type' => $this->message->sender_type,
            'read_at' => $this->message->read_at,
            'created_at' => $this->message->created_at
        ];
    }
}
