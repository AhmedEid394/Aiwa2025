<?php

namespace App\Notifications;

use App\Models\FcmToken;
use App\Models\ServiceProvider;
use App\Models\User;
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
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {

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
                ]);

    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->serviceRequest->title,
            'description' => $this->serviceRequest->description,
        ];
    }
    public function databaseType(object $notifiable): string
    {
        return 'service-request';
    }

}
