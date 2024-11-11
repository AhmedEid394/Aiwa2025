<?php

namespace App\Notifications;

use App\Models\FcmToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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

    public function toFcm($notifiable): FcmMessage
    {
        Log::info('toFcm');
        return (new FcmMessage(notification: new FcmNotification(
            title: $this->serviceRequest->title,
            body: $this->serviceRequest->description,
        )))
            ->data(['data1' => 'value', 'data2' => 'value2'])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default'
                        ],
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'analytics',
                    ],
                ],
            ])
            ->token(FcmToken::where('user_id', $notifiable->user_id)
                ->orWhere('user_id',$notifiable->provider_id)->first()->token);
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
