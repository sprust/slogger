<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Contracts\Actions\Mutations\UpdateTraceManyActionInterface;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

class TraceUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 120;

    public int $backoff = 1;

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

        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff);
        } else {
            $this->delete();

            $ids = array_map(
                fn(TraceUpdateParameters $parameters) => $parameters->traceId,
                $this->parametersList->getItems()
            );

            $idsView = implode(',', $ids);

            report(new RuntimeException("Not updated some traces from [$idsView]"));
        }
    }
}
