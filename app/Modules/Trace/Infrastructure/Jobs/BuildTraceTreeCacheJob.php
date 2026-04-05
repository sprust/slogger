<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Jobs;

use App\Modules\Trace\Domain\Actions\Mutations\BuildTraceTreeCacheAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BuildTraceTreeCacheJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 2;

    public function __construct(
        private readonly string $rootTraceId,
        private readonly string $version,
    ) {
        $this->onConnection(config('module-trace.queue.connection'))
            ->onQueue(config('module-trace.queue.name'));
    }

    public function handle(
        BuildTraceTreeCacheAction $buildTraceTreeCacheAction,
    ): void {
        $buildTraceTreeCacheAction->handle(
            rootTraceId: $this->rootTraceId,
            version: $this->version,
        );
    }
}
