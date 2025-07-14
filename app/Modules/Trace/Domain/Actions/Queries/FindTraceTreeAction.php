<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;

readonly class FindTraceTreeAction implements FindTraceTreeActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private TraceTreeCacheRepositoryInterface $traceTreeCacheRepository,
    ) {
    }

    public function handle(string $traceId, bool $fresh, bool $isChild): ?TraceTreeRawIterator
    {
        if ($isChild) {
            $rootTraceId = $traceId;
        } else {
            $rootTraceId = $this->traceTreeRepository->findParentTraceId(
                traceId: $traceId
            );

            if (!$rootTraceId) {
                return null;
            }
        }

        $rootTrace = $this->traceRepository->findOneDetailByTraceId(
            traceId: $rootTraceId
        );

        if (is_null($rootTrace)) {
            return null;
        }

        $needCache = $fresh
            || !$this->traceTreeCacheRepository->has(
                rootTraceId: $rootTraceId
            );

        if ($needCache) {
            $additionalTraceIds = $isChild
                ? $this->traceTreeRepository->findChainToParentTraceId(
                    traceId: $traceId
                )
                : [];

            $this->cacheTree(
                rootTrace: $rootTrace,
                additionalTraceIds: $additionalTraceIds
            );
        }

        return $this->traceTreeCacheRepository->findMany(
            rootTraceId: $rootTraceId
        );
    }

    /**
     * @param string[] $additionalTraceIds
     */
    private function cacheTree(TraceDto $rootTrace, array $additionalTraceIds): void
    {
        $rootTraceId = $rootTrace->traceId;

        $this->traceTreeCacheRepository->delete(
            rootTraceId: $rootTraceId
        );

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

        $childIds = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId(
            traceId: $rootTraceId
        );

        $childIds = array_unique([
            ...$childIds,
            ...$additionalTraceIds,
        ]);

        foreach (array_chunk(array: $childIds, length: 500) as $childIdsChunk) {
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

            $this->traceTreeCacheRepository->createMany(
                rootTraceId: $rootTraceId,
                parametersList: $cacheParametersList
            );
        }
    }
}
