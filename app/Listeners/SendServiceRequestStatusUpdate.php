<?php

namespace App\Listeners;

use App\Models\ServiceProvider;
use App\Notifications\ServiceRequestStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendServiceRequestStatusUpdate
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
        $serviceRequest= $event->serviceRequest;
        $providers= ServiceProvider::where('sub_category_id', $serviceRequest->sub_category_id)->get();
        foreach ($providers as $provider) {
            $provider->notify(new ServiceRequestStatusNotification($serviceRequest));
        }
    }
}
