<?php

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
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
    public function handle(UpdateTraceManyActionInterface $updateTraceManyAction): void
    {
        if ($updateTraceManyAction->handle($this->parametersList) === $this->parametersList->count()) {
            $this->delete();

            return;
        }

        $this->release(2);
    }
}
