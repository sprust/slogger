<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Infrastructure\Tasks\BuildTraceDynamicIndexesTask;
use Illuminate\Console\Command;
use SConcur\Laravel\Tasks\TaskPool;

/**
 * Runs the dynamic index monitor on its own.
 *
 * Kept as a familiar entry point for local work; in production the same task runs inside
 * the pool alongside the others, driven by the same loop.
 */
class StartMonitorTraceDynamicIndexesCommand extends Command
{
    protected $signature = 'trace-dynamic-indexes:monitor:start';

    protected $description = 'Start the dynamic trace index monitor (that task alone)';

    public function handle(TaskPool $pool): int
    {
        return $pool->run([BuildTraceDynamicIndexesTask::NAME]);
    }
}
