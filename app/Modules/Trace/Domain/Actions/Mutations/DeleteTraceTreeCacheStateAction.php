<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;

readonly class DeleteTraceTreeCacheStateAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private DispatcherInterface $dispatcher
    ) {
    }

    public function handle(string $rootTraceId): void
    {
        $this->traceTreeCacheStateRepository->deleteByRootTraceId(
            $rootTraceId,
        );

        $this->dispatcher->event(
            new TraceTreeCacheDeleteRequestedEvent(
                rootTraceId: $rootTraceId,
            )
        );
    }
}
