<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheSliceObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

readonly class TraceTreeCacheBuilderService
{
    /**
     * The shortest gap between two progress announcements of one build, in seconds.
     *
     * Chunks land close together on a wide tree, and a frame per chunk would replace a
     * poll every second with a stream faster than that — the problem, not the fix.
     */
    private const float PROGRESS_INTERVAL_SECONDS = 0.5;

    private const int TRAVERSAL_BATCH_COUNT = 1000;

    private const int SLICE_PARENTS_COUNT = 1000;

    private const int ROOT_DEPTH = 0;

    /**
     * The depth the traversal never asks for, so what is written with it is cached and
     * never expanded: the chain above the root belongs to the tree but not below it.
     */
    private const int ANCESTOR_DEPTH = -1;

    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
        private Dispatcher $events,
    ) {
    }

    public function handleSlice(
        string $rootTraceId,
        string $version,
        int $depth,
        ?string $afterId
    ): TraceTreeCacheSliceObject {
        $lastProgressAt = 0.0;

        if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
            return $this->stopped();
        }

        if ($depth === self::ROOT_DEPTH && is_null($afterId)) {
            $this->createRoot($rootTraceId, $version);
        }

        $page = $this->traceTreeCacheRepository->findTraceIdsPage(
            rootTraceId: $rootTraceId,
            depth: $depth,
            afterId: $afterId,
            limit: self::SLICE_PARENTS_COUNT
        );

        $childIdsChunks = $this->traceTreeRepository->findChildrenTraceIds(
            parentTraceIds: $page->traceIds,
            batchCount: self::TRAVERSAL_BATCH_COUNT
        );

        foreach ($childIdsChunks as $childIdsChunk) {
            if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
                return $this->stopped();
            }

            $this->createTraceTree(
                rootTraceId: $rootTraceId,
                version: $version,
                childIdsChunk: $childIdsChunk,
                depth: $depth + 1,
            );

            $this->announceProgress($rootTraceId, $lastProgressAt);
        }

        if (count($page->traceIds) >= self::SLICE_PARENTS_COUNT) {
            return new TraceTreeCacheSliceObject(
                stopped: false,
                finished: false,
                nextDepth: $depth,
                nextAfterId: $page->lastId
            );
        }

        if ($this->traceTreeCacheRepository->existsByDepth($rootTraceId, $depth + 1)) {
            return new TraceTreeCacheSliceObject(
                stopped: false,
                finished: false,
                nextDepth: $depth + 1,
                nextAfterId: null
            );
        }

        $this->createAncestors($rootTraceId, $version);

        return new TraceTreeCacheSliceObject(
            stopped: false,
            finished: true,
            nextDepth: null,
            nextAfterId: null
        );
    }

    private function stopped(): TraceTreeCacheSliceObject
    {
        return new TraceTreeCacheSliceObject(
            stopped: true,
            finished: false,
            nextDepth: null,
            nextAfterId: null
        );
    }

    private function createRoot(string $rootTraceId, string $version): void
    {
        $rootTrace = $this->traceRepository->findOneDetailByTraceId(
            traceId: $rootTraceId
        );

        if ($rootTrace === null) {
            throw new RuntimeException('Root trace not found.');
        }

        $created = $this->traceTreeCacheRepository->createMany(
            rootTraceId: $rootTraceId,
            depth: self::ROOT_DEPTH,
            parametersList: [
                new CreateTraceTreeCacheParameters(
                    serviceId: $rootTrace->serviceId,
                    parentTraceId: $rootTrace->parentTraceId,
                    traceId: $rootTrace->traceId,
                    type: $rootTrace->type,
                    tags: $rootTrace->tags,
                    status: $rootTrace->status,
                    duration: $rootTrace->duration,
                    memory: $rootTrace->memory,
                    cpu: $rootTrace->cpu,
                    loggedAt: $rootTrace->loggedAt,
                ),
            ]
        );

        $this->traceTreeCacheStateRepository->incrementCount(
            rootTraceId: $rootTraceId,
            version: $version,
            count: $created,
        );
    }

    private function createAncestors(string $rootTraceId, string $version): void
    {
        $ancestorTraceIds = $this->traceTreeRepository->findChainToParentTraceId(
            traceId: $rootTraceId
        );

        if ($ancestorTraceIds === []) {
            return;
        }

        $this->createTraceTree(
            rootTraceId: $rootTraceId,
            version: $version,
            childIdsChunk: $ancestorTraceIds,
            depth: self::ANCESTOR_DEPTH,
        );
    }

    /**
     * Announces how far this build has got, at most once every PROGRESS_INTERVAL_SECONDS.
     *
     * The count lives in the state document — incrementCount() has just raised it — so
     * the state is read back rather than counted here. That read is what the throttle is
     * really rationing.
     */
    private function announceProgress(string $rootTraceId, float &$lastAnnouncedAt): void
    {
        $now = microtime(true);

        if (($now - $lastAnnouncedAt) < self::PROGRESS_INTERVAL_SECONDS) {
            return;
        }

        $lastAnnouncedAt = $now;

        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);

        if ($state === null) {
            return;
        }

        $this->events->dispatch(new TraceTreeCacheStateChangedEvent($state));
    }

    /**
     * @param string[] $childIdsChunk
     */
    private function createTraceTree(
        string $rootTraceId,
        string $version,
        array $childIdsChunk,
        int $depth
    ): void {
        $foundTraces = $this->traceRepository->findByTraceIds(
            traceIds: $childIdsChunk
        );

        /** @var CreateTraceTreeCacheParameters[] $cacheParametersList */
        $cacheParametersList = [];

        foreach ($foundTraces as $foundTrace) {
            $cacheParametersList[] = new CreateTraceTreeCacheParameters(
                serviceId: $foundTrace->serviceId,
                parentTraceId: $foundTrace->parentTraceId,
                traceId: $foundTrace->traceId,
                type: $foundTrace->type,
                tags: $foundTrace->tags,
                status: $foundTrace->status,
                duration: $foundTrace->duration,
                memory: $foundTrace->memory,
                cpu: $foundTrace->cpu,
                loggedAt: $foundTrace->loggedAt,
            );
        }

        if ($cacheParametersList === []) {
            return;
        }

        $created = $this->traceTreeCacheRepository->createMany(
            rootTraceId: $rootTraceId,
            depth: $depth,
            parametersList: $cacheParametersList
        );

        $this->traceTreeCacheStateRepository->incrementCount(
            rootTraceId: $rootTraceId,
            version: $version,
            count: $created,
        );
    }
}
