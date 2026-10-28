<?php

declare(strict_types=1);

namespace App\Services\Sconcur\Listeners;

use Illuminate\Support\Facades\Log;
use SConcur\Laravel\Servers\Events\WorkerForked;

/**
 * Tags every log line of a worker with its group and slot, so the lines of the workers can be told apart.
 */
class TagLogWithWorkerListener
{
    public function handle(WorkerForked $event): void
    {
        Log::shareContext([
            'worker' => sprintf('%s#%d', $event->workerContext->group, $event->workerContext->slot),
        ]);
    }
}
