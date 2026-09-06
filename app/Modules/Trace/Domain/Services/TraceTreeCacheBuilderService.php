<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
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

    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
        private Dispatcher $events,
    ) {
    }

    public function handle(string $rootTraceId, string $version): bool
    {
        $lastProgressAt = 0.0;

        $rootTrace = $this->traceRepository->findOneDetailByTraceId(
            traceId: $rootTraceId
        );

        if ($rootTrace === null) {
            throw new RuntimeException('Root trace not found.');
        }

        $canContinue = $this->isShouldContinueBuildTraceTreeCacheAction->handle(
            rootTraceId: $rootTraceId,
            version: $version
        );

        if ($canContinue === false) {
            return false;
        }

        $this->traceTreeCacheRepository->delete(
            rootTraceId: $rootTraceId
        );

        $canContinue = $this->isShouldContinueBuildTraceTreeCacheAction->handle($rootTraceId, $version);

        if ($canContinue === false) {
            return false;
        }

        $this->traceTreeCacheRepository->createMany(
            rootTraceId: $rootTraceId,
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
            count: 1,
        );

        foreach ($this->traceTreeRepository->findTraceIdsInTreeByParentTraceId($rootTraceId, 1000) as $childIdsChunk) {
            $canContinue = $this->isShouldContinueBuildTraceTreeCacheAction->handle(
                rootTraceId: $rootTraceId,
                version: $version
            );

            if ($canContinue === false) {
                return false;
            }

            $this->createTraceTree(
                rootTraceId: $rootTraceId,
                version: $version,
                childIdsChunk: $childIdsChunk,
            );

            $this->announceProgress($rootTraceId, $lastProgressAt);
        }

        $additionalTraceIds = $this->traceTreeRepository->findChainToParentTraceId(
            traceId: $rootTraceId
        );

        if ($additionalTraceIds !== []) {
            $canContinue = $this->isShouldContinueBuildTraceTreeCacheAction->handle(
                $rootTraceId,
                $version
            );

            if ($canContinue === false) {
                return false;
            }

            $this->createTraceTree(
                rootTraceId: $rootTraceId,
                version: $version,
                childIdsChunk: $additionalTraceIds,
            );

            $this->announceProgress($rootTraceId, $lastProgressAt);
        }

        return $this->isShouldContinueBuildTraceTreeCacheAction->handle(
            rootTraceId: $rootTraceId,
            version: $version
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
    private function createTraceTree(string $rootTraceId, string $version, array $childIdsChunk): void
    {
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

        $this->traceTreeCacheRepository->createMany(
            rootTraceId: $rootTraceId,
            parametersList: $cacheParametersList
        );

        $this->traceTreeCacheStateRepository->incrementCount(
            rootTraceId: $rootTraceId,
            version: $version,
            count: count($cacheParametersList),
        );
    }
}
