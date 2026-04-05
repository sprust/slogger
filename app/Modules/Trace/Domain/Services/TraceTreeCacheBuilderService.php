<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use RuntimeException;

readonly class TraceTreeCacheBuilderService
{
    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
        private IsShouldContinueBuildTraceTreeCacheAction $isShouldContinueBuildTraceTreeCacheAction,
    ) {
    }

    public function handle(string $rootTraceId, string $version): bool
    {
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
        }

        return $this->isShouldContinueBuildTraceTreeCacheAction->handle(
            rootTraceId: $rootTraceId,
            version: $version
        );
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
