<?php

namespace App\Modules\TracesCollector\Jobs;

use App\Modules\TracesCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TracesCollector\Services\TracesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TraceCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly TraceCreateParametersList $parametersList)
    {
        $this->onConnection(config('queue.queues.creating.connection'))
            ->onQueue(config('queue.queues.creating.name'));
    }

    /**
     * Execute the job.
     */
    public function handle(TracesService $tracesService): void
    {
        $tracesService->createMany($this->parametersList);
    }
}
