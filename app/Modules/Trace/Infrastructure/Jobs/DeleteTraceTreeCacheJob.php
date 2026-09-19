<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DeleteTraceTreeCacheJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 2;

    public function __construct(
        private readonly string $rootTraceId,
        private readonly ?string $buildVersion = null,
    ) {
        $this->onConnection(config('module-trace.queue.connection'))
            ->onQueue(config('module-trace.queue.name'));
    }

    public function handle(
        DeleteTraceTreeCacheAction $deleteTraceTreeCacheAction,
    ): void {
        $deleteTraceTreeCacheAction->handle(
            rootTraceId: $this->rootTraceId,
            buildVersion: $this->buildVersion,
        );
    }
}
