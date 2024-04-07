<?php

namespace App\Modules\TraceCollector\Framework\Jobs;

use App\Modules\TraceCollector\Domain\Actions\TraceCreateManyAction;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
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
    public function handle(TraceCreateManyAction $traceCreateManyAction): void
    {
        $traceCreateManyAction->handle($this->parametersList);
    }
}
