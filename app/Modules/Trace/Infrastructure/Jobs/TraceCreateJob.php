<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Contracts\Actions\Mutations\CreateTraceManyActionInterface;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TraceCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 120;

    public int $backoff = 1;

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
    public function handle(CreateTraceManyActionInterface $createTraceManyAction): void
    {
        $createTraceManyAction->handle($this->parametersList);
    }
}
