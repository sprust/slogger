<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Contracts\Events\Dispatcher;

readonly class DeleteTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private Dispatcher $events,
    ) {
    }

    public function handle(string $rootTraceId): void
    {
        $this->traceTreeCacheStateRepository->deleteByRootTraceId(
            rootTraceId: $rootTraceId
        );

        $this->events->dispatch(
            new TraceTreeCacheDeleteRequestedEvent(
                rootTraceId: $rootTraceId
            )
        );
    }
}
