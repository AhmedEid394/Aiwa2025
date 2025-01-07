<?php

namespace App\Listeners;

use App\Models\ServiceProvider;
use App\Notifications\BmCashoutStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BmCashoutStatusUpdateListener
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
        $status = $event->status;
        // Send notification to user
        $user= ServiceProvider::where("provider_id",$event->user_id)->first();
        if ($user) {
            $user->notify(new BmCashoutStatusNotification($status));
        }
    }
}
