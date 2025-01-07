<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\BmCashoutStatusController;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            try {
                app(BmCashoutStatusController::class)->checkTransactionStatuses();
            } catch (\Exception $e) {
                Log::error('Transaction status check failed: ' . $e->getMessage());
            }
        })->everyThirtyMinutes()
            ->withoutOverlapping()  // Prevent overlapping
            ->runInBackground();    // Run in background
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
