<?php
namespace App\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Events\ServiceRequested;

class ServiceRequestNotification extends Notification
{
use Queueable;

public $serviceRequest;

/**
* Create a new notification instance.
*/
public function __construct($serviceRequest)
{
$this->serviceRequest = $serviceRequest;
}

/**
* Get the notification's delivery channels.
*
* @return array<int, string>
*/
public function via(object $notifiable): array
{
return ['database', 'broadcast'];
}

/**
* Get the mail representation of the notification.
*/
public function toMail(object $notifiable): MailMessage
{
return (new MailMessage)
->line('The introduction to the notification.')
->action('Notification Action', url('/'))
->line('Thank you for using our application!');
}

/**
* Get the array representation of the notification.
*
* @return array<string, mixed>
*/
public function toArray(object $notifiable): array
{
return [
'title' => $this->serviceRequest->title,
'description' => $this->serviceRequest->description,
];
}

/**
* Get the notification's database type.
*
* @return string
*/
public function databaseType(object $notifiable): string
{
return 'service-request';
}

/**
* Get the channels the event should broadcast on.
*
* @return Channel
 */
public function broadcastOn()
{
return new Channel('service-request');
}

/**
* Get the data to broadcast.
*
* @return array
*/
public function broadcastWith()
{
return [
'serviceRequest' => $this->serviceRequest
];
}

/**
* Get the event name to broadcast.
*
* @return string
*/
public function broadcastAs()
{
return 'service-requested';
}
}
