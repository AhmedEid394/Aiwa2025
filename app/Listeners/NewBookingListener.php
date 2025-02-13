<?php

namespace App\Listeners;

use App\Models\ServiceProvider;
use App\Notifications\NewBookingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NewBookingListener
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
        Log::info('New booking received', ['booking' => $booking]);
        $provider = $booking->service->provider;
        Log::info('Provider found', ['provider' => $provider]);
        if ($provider instanceof ServiceProvider) {
            $provider->notify(new NewBookingNotification($booking));
        }
    }
}
