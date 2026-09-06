<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

readonly class BuildTraceTreeCacheAction
{
    public function __construct(
        private TraceTreeCacheBuilderService $traceTreeCacheBuilderService,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
        private Dispatcher $events,
    ) {
    }

    public function handle(string $rootTraceId, string $version): void
    {
        // Announced outside build(), and outside its catch: markFailed() filters by
        // version alone, so anything thrown while announcing a finished build would be
        // caught there and record that build as failed.
        $this->announce($rootTraceId, $this->build($rootTraceId, $version));
    }

    /**
     * Runs the build and writes down how it ended.
     *
     * Answers whether this call is the one that marked the state — false when the build
     * did not complete, or when the mark matched nothing because the build was canceled
     * or superseded while it ran and the state on record is somebody else's.
     */
    private function build(string $rootTraceId, string $version): bool
    {
        try {
            if (
                !$this->isShouldContinueBuildTraceTreeCacheAction->handle(
                    rootTraceId: $rootTraceId,
                    version: $version
                )
            ) {
                return false;
            }

            $completed = $this->traceTreeCacheBuilderService->handle(
                rootTraceId: $rootTraceId,
                version: $version,
            );

            if (!$completed) {
                return false;
            }

            return $this->traceTreeCacheStateRepository->markFinished(
                rootTraceId: $rootTraceId,
                version: $version,
            );
        } catch (Throwable $exception) {
            if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
                return false;
            }

            return $this->traceTreeCacheStateRepository->markFailed(
                rootTraceId: $rootTraceId,
                version: $version,
                error: $exception::class . ': ' . ($exception->getMessage() ?: 'Unknown error'),
            );
        }
    }

    /**
     * Tells whoever is watching this tree that it has stopped moving.
     *
     * The state is read back rather than assembled here: mark* answers with whether it
     * matched, not with what it wrote, and the announcement carries the whole state.
     */
    private function announce(string $rootTraceId, bool $marked): void
    {
        if (!$marked) {
            return;
        }

        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);

        if ($state === null) {
            return;
        }

        $this->events->dispatch(new TraceTreeCacheStateChangedEvent($state));
    }
}
