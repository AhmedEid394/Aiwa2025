<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\BmCashoutStatusController;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule)
    {
        // Check transaction statuses every 30 minutes
        $schedule->call(function () {
            app(BmCashoutStatusController::class)->checkTransactionStatuses();
        })->everyThirtyMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
