<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use Illuminate\Contracts\Events\Dispatcher;

readonly class DeleteTraceTreeCacheAction
{
    private const int CHUNK_COUNT = 10000;

    public function __construct(
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
        private Dispatcher $events,
    ) {
    }

    public function handle(string $rootTraceId, ?string $buildVersion = null): void
    {
        if (
            !is_null($buildVersion)
            && !$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $buildVersion)
        ) {
            return;
        }

        $deleted = $this->traceTreeCacheRepository->deleteChunk(
            rootTraceId: $rootTraceId,
            limit: self::CHUNK_COUNT
        );

        if ($deleted >= self::CHUNK_COUNT) {
            $this->events->dispatch(
                new TraceTreeCacheDeleteRequestedEvent(
                    rootTraceId: $rootTraceId,
                    buildVersion: $buildVersion
                )
            );

            return;
        }

        if (is_null($buildVersion)) {
            return;
        }

        $this->events->dispatch(
            new TraceTreeCacheBuildRequestedEvent(
                rootTraceId: $rootTraceId,
                version: $buildVersion
            )
        );
    }
}
