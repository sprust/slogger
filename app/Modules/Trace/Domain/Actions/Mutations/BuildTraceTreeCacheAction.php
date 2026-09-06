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

            $marked = $this->traceTreeCacheStateRepository->markFinished(
                rootTraceId: $rootTraceId,
                version: $version,
            );

            $this->announce($rootTraceId, $marked);
        } catch (Throwable $exception) {
            if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
                return;
            }

            $marked = $this->traceTreeCacheStateRepository->markFailed(
                rootTraceId: $rootTraceId,
                version: $version,
                error: $exception::class . ': ' . ($exception->getMessage() ?: 'Unknown error'),
            );

            $this->announce($rootTraceId, $marked);
        }
    }

    /**
     * Tells whoever is watching this tree that it has stopped moving.
     *
     * The state is read back rather than assembled here: mark* answers with whether it
     * matched, not with what it wrote, and the announcement carries the whole state. One
     * extra read per build is the price, and a build is not a frequent thing.
     *
     * Nothing is announced when the mark matched nothing — the build was canceled or
     * superseded while it ran, and the state on record is somebody else's.
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
