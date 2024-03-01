<?php

namespace App\Modules\TracesCollector\Jobs;

use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TracesCollector\Services\TracesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TraceUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly TraceUpdateParametersList $parametersList)
    {
        $this->onConnection(config('queue.queues.creating.connection'))
            ->onQueue(config('queue.queues.creating.name'));
    }

    /**
     * Execute the job.
     */
    public function handle(TracesService $tracesService): void
    {
        if ($tracesService->updateMany($this->parametersList) === $this->parametersList->count()) {
            $this->delete();

            return;
        }

        $this->release(2);
    }
}
