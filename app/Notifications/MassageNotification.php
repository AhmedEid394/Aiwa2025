<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class MassageNotification extends Notification
{
    use Queueable;

    protected $message;

    /**
     * Create a new notification instance.
     *
     * @param $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Format the FCM message for the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\Fcm\FcmMessage
     */
    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
                title: 'New Message',
                body: $this->message->message
            )))
            ->data([
                'chat_id' => $this->message->chat_id,
                'sender_type' => $this->message->sender_type,
                'created_at' => $this->message->created_at,
            ])
            ->custom([
                'android' => [
                    'notification' => [
                        'color' => '#0A0A0A',
                        'sound' => 'default',
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'message_analytics',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => 'New Message',
                                'body' => $this->message->message,
                            ],
                            'sound' => 'default',
                        ],
                    ],
                    'fcm_options' => [
                        'analytics_label' => 'message_analytics',
                    ],
                ],
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'chat_id' => $this->message->chat_id,
            'message' => $this->message->message,
            'sender_type' => $this->message->sender_type,
            'created_at' => $this->message->created_at,
        ];
    }

    /**
     * Specify the database notification type.
     *
     * @param object $notifiable
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'message';
    }
}
