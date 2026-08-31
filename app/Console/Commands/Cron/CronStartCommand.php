<?php

declare(strict_types=1);

namespace App\Console\Commands\Cron;

use App\Services\Tasks\CronTask;
use Illuminate\Console\Command;
use SConcur\Laravel\Tasks\TaskPool;

/**
 * Runs the cron on its own.
 *
 * Kept as a familiar entry point for local work; in production the same task runs inside
 * the pool alongside the others. Either way it is the pool's loop that drives it, so
 * there is one implementation of the loop and no second behaviour to keep in step.
 */
class CronStartCommand extends Command
{
    protected $signature = 'cron:start';

    protected $description = 'Start the cron (the cron task alone, as sconcur:tasks:start --only=cron)';

    public function handle(TaskPool $pool): int
    {
        return $pool->run([CronTask::NAME]);
    }
}
