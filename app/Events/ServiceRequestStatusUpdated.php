<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $serviceRequest;
    /**
     * Create a new event instance.
     */
    public function __construct($serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */

    public function broadcastOn()
    {
        return new Channel('service-request-status.' . $this->serviceRequest->id);
    }

    public function broadcastWith()
    {
        return [
            'serviceRequest' => $this->serviceRequest
        ];
    }

    public function broadcastAs()
    {
        return 'service-request-status-updated';
    }
}
