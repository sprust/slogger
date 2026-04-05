<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Throwable;

readonly class BuildTraceTreeCacheAction
{
    public function __construct(
        private TraceTreeCacheBuilderService $traceTreeCacheBuilderService,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
    ) {
    }

    public function handle(string $rootTraceId, string $version): void
    {
        try {
            if (
                !$this->isShouldContinueBuildTraceTreeCacheAction->handle(
                    rootTraceId: $rootTraceId,
                    version: $version
                )
            ) {
                return;
            }

            $completed = $this->traceTreeCacheBuilderService->handle(
                rootTraceId: $rootTraceId,
                version: $version,
            );

            if (!$completed) {
                return;
            }

            $this->traceTreeCacheStateRepository->markFinished(
                rootTraceId: $rootTraceId,
                version: $version,
            );
        } catch (Throwable $exception) {
            if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
                return;
            }

            $this->traceTreeCacheStateRepository->markFailed(
                rootTraceId: $rootTraceId,
                version: $version,
                error: $exception::class . ': ' . ($exception->getMessage() ?: 'Unknown error'),
            );
        }
    }
}
