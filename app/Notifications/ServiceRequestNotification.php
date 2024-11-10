<?php

namespace App\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class ServiceRequestNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $serviceRequest;

    public function __construct($serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setData([
                'service_request_id' => (string)$this->serviceRequest->id,
                'type' => 'service_request'
            ])
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle('New Service Request')
                    ->setBody($this->serviceRequest->title)
            );
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->serviceRequest->title,
            'description' => $this->serviceRequest->description,
        ];
    }

    public function broadcastOn()
    {
        return new Channel('service-request');
    }

    public function broadcastWith()
    {
        return [
            'serviceRequest' => $this->serviceRequest,
        ];
    }

    public function broadcastAs()
    {
        return 'service-requested';
    }
}
