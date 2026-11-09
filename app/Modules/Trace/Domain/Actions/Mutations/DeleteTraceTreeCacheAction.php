<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;

readonly class DeleteTraceTreeCacheAction
{
    private const int CHUNK_COUNT = 10000;

    public function __construct(private TraceTreeCacheRepository $traceTreeCacheRepository, private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction, private DispatcherInterface $dispatcher)
    {
    }

    public function handle(string $rootTraceId, ?string $buildVersion = null): void
    {
        if (
            !is_null($buildVersion) && !$this->isShouldContinueBuildTraceTreeCacheAction->handle(
                rootTraceId: $rootTraceId,
                version: $buildVersion,
            )
        ) {
            return;
        }

        $deleted = $this->traceTreeCacheRepository->deleteChunk(
            rootTraceId: $rootTraceId,
            limit: self::CHUNK_COUNT,
        );

        if ($deleted >= self::CHUNK_COUNT) {
            $this->dispatcher->event(
                new TraceTreeCacheDeleteRequestedEvent(
                    rootTraceId: $rootTraceId,
                    buildVersion: $buildVersion,
                )
            );

            return;
        }

        if (is_null($buildVersion)) {
            return;
        }

        $this->dispatcher->event(
            new TraceTreeCacheBuildRequestedEvent(
                rootTraceId: $rootTraceId,
                version: $buildVersion,
            )
        );
    }
}
