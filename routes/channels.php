<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register  event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('service-request', function ($user, $serviceRequest) {
    Log::info('ServiceRequest channel authorized', ['user' => $user, 'serviceRequest' => $serviceRequest]);
    return true;
});

Broadcast::channel('booking.{booking}', function ($user, $booking) {
    return $user->id === $booking->user_id;
});

Broadcast::channel('service-request-status.{serviceRequest}', function ($user, $serviceRequest) {
    return $user->id === $serviceRequest->user_id;
});

Broadcast::channel('booking-status.{booking}', function ($user, $booking) {
    return $user->id === $booking->user_id;
});

Broadcast::channel('system-notification.{user}', function ($user) {
    return $user->id === $user->id;
});
