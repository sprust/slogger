<?php

namespace App\Modules\TraceCollector\Framework\Jobs;

use App\Modules\TraceCollector\Domain\Actions\TraceUpdateManyAction;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
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
    public function handle(TraceUpdateManyAction $traceUpdateManyAction): void
    {
        if ($traceUpdateManyAction->handle($this->parametersList) === $this->parametersList->count()) {
            $this->delete();

            return;
        }

        $this->release(2);
    }
}
