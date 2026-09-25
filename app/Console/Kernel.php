<?php

namespace App\Console;

use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Dashboard\Infrastructure\Jobs\RefreshDatabaseStatCacheJob;
use App\Modules\Logs\Infrastructure\Commands\CleanLogsCommand;
use App\Modules\Logs\Infrastructure\Commands\IndexLogsCommand;
use App\Modules\User\Infrastructure\Commands\DeleteExpiredUserTokensCommand;
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
        $schedule->job(RefreshDatabaseStatCacheJob::class)->everyFiveMinutes();
        $schedule->command(DeleteExpiredUserTokensCommand::class)->daily();
        $schedule->command(IndexLogsCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanLogsCommand::class)->daily()->withoutOverlapping();
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
