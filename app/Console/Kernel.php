<?php

namespace App\Console;

use App\Modules\Cleaner\Framework\Commands\ClearTracesCommand;
use App\Modules\Dashboard\Framework\Http\Jobs\CacheServiceStatJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
         // TODO: move to long job
         $schedule->command(ClearTracesCommand::class)->everyFifteenMinutes();
         // TODO: need that?
         //$schedule->job(CacheServiceStatJob::class)->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
