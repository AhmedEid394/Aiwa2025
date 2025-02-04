<?php

namespace App\Console;

use App\Http\Controllers\SignerService;
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
                // Write to both Laravel's default log and scheduler log
                $message = 'Starting transaction status check at: ' . now();
                Log::info($message);
                file_put_contents(
                    storage_path('logs/scheduler.log'),
                    "[" . now() . "] " . $message . PHP_EOL,
                    FILE_APPEND
                );

//                app(BmCashoutStatusController::class)->checkTransactionStatuses();
                $bmCashoutStatusController = new BmCashoutStatusController(new SignerService());
                $bmCashoutStatusController->checkTransactionStatuses();
                $message = 'Transaction status check completed at: ' . now();
                Log::info($message);
                file_put_contents(
                    storage_path('logs/scheduler.log'),
                    "[" . now() . "] " . $message . PHP_EOL,
                    FILE_APPEND
                );
            } catch (\Exception $e) {
                $error = 'Transaction status check failed: ' . $e->getMessage();
                Log::error($error);
                file_put_contents(
                    storage_path('logs/scheduler.log'),
                    "[" . now() . "] ERROR: " . $error . PHP_EOL,
                    FILE_APPEND
                );
            }
        })->everyThirtyMinutes();    // Run in background
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
