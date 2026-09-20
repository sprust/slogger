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

    /**
     * How many children a slice sets out to write before handing the level on, and how
     * long it may take doing it.
     *
     * A page of parents is not a unit of work — its children are. Near the leaves a
     * thousand parents yield a handful of nodes or none at all, and the slice still pays
     * for a delivery, a job, the state, the page and an aggregation across every shard;
     * that is the whole of the slowdown at the end of a large build. So a slice keeps
     * taking pages until it has written something worth the round trip.
     *
     * The budget is what keeps the other end honest, and it is the one that must not be
     * raised lightly: short deliveries are why the build is a chain of jobs at all, and
     * it also bounds how long a cancel waits for the page in flight. The target is a
     * floor rather than a cap — a page whose children run past it is never cut in half.
     */
    private const int SLICE_TARGET_CHILDREN = 5000;

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
        private float $sliceTimeBudgetSeconds = 5.0,
    ) {
    }

    public function handleSlice(
        string $rootTraceId,
        string $version,
        int $depth,
        ?string $afterId
    ): TraceTreeCacheSliceObject {
        $lastProgressAt = 0.0;

        $startedAt = microtime(true);

        $writtenCount = 0;

        if (!$this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version)) {
            return $this->stopped();
        }

        if ($depth === self::ROOT_DEPTH && is_null($afterId)) {
            $this->createRoot($rootTraceId, $version);
        }

        while (true) {
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

                $writtenCount += $this->createTraceTree(
                    rootTraceId: $rootTraceId,
                    version: $version,
                    childIdsChunk: $childIdsChunk,
                    depth: $depth + 1,
                );

                $this->announceProgress($rootTraceId, $lastProgressAt);
            }

            $lastId = $page->lastId;

            // The level is done when its last page comes back short. A page that filled
            // up has more behind it, and $lastId is where the next one starts — from this
            // slice if there is room left in it, from the next if there is not.
            if (count($page->traceIds) < self::SLICE_PARENTS_COUNT || $lastId === null) {
                break;
            }

            $afterId = $lastId;

            if (
                $writtenCount >= self::SLICE_TARGET_CHILDREN
                || (microtime(true) - $startedAt) >= $this->sliceTimeBudgetSeconds
            ) {
                return new TraceTreeCacheSliceObject(
                    stopped: false,
                    finished: false,
                    nextDepth: $depth,
                    nextAfterId: $afterId
                );
            }
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
     *
     * @return int how many nodes this chunk added to the tree
     */
    private function createTraceTree(
        string $rootTraceId,
        string $version,
        array $childIdsChunk,
        int $depth
    ): int {
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
            return 0;
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

        return $created;
    }
}
