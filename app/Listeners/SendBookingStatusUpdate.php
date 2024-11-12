<?php

namespace App\Listeners;

use App\Notifications\BookingStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBookingStatusUpdate
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
     */
    public function handle(object $event): void
    {
        $booking = $event->booking;
        $user=null;
        if($booking->user_type === 'user') {
            $user = $booking->user();
            Log::info('user booking');
        }elseif($booking->user_type === 'Provider'){
            $user = $booking->provider();
            Log::info('provider booking');
        }
        if($user){
            $user->notify(new BookingStatusNotification($booking));
        }
    }
}
