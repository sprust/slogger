<?php

namespace App\Modules\Traces\Jobs;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Services\TracesService;
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
        $this->onConnection(config('queue.queues.connection'))
            ->onQueue(config('queue.queues.name'));
    }

    /**
     * Execute the job.
     */
    public function handle(TracesService $tracesService): void
    {
        $tracesService->createMany($this->parametersList);
    }
}
