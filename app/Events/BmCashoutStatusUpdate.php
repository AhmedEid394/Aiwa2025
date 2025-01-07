<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BmCashoutStatusUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $status;
    public $user_id;
    /**
     * Create a new event instance.
     */
    public function __construct($status, $user_id)
    {
        $this->status = $status;
        $this->user_id = $user_id;
    }

}
