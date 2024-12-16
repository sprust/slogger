<?php

namespace App\Console;

use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Trace\Infrastructure\Jobs\DeleteOldEmptyCollectionsJob;
use App\Modules\Trace\Infrastructure\Jobs\FreshTraceTreesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(ClearTracesJob::class)->hourly();
        $schedule->job(DeleteOldEmptyCollectionsJob::class)->hourlyAt(30);
        $schedule->job(FreshTraceTreesJob::class)->hourlyAt(15);
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
