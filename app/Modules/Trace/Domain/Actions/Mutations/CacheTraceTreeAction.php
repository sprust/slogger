<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\CacheTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeMapDepthObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeMapObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use Illuminate\Support\Carbon;

readonly class CacheTraceTreeAction implements CacheTraceTreeActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private TraceTreeCacheRepositoryInterface $traceTreeCacheRepository,
    ) {
    }

    public function handle(string $traceId): void
    {
        $parentTraceId = $this->traceTreeRepository->findParentTraceId(
            traceId: $traceId
        );

        if (!$parentTraceId) {
            return;
        }

        $parent = $this->traceRepository->findOneDetailByTraceId(
            traceId: $parentTraceId
        );

        if (is_null($parent)) {
            return;
        }

        $this->traceTreeCacheRepository->deleteByParentTraceId(
            parentTraceId: $parentTraceId
        );

        $parentTree = new TraceTreeMapObject(
            traceId: $parent->traceId,
            children: [],
            loggedAt: $parent->loggedAt->toDateTimeString('microsecond'),
        );

        $this->traceTreeCacheRepository->createMany(
            parentTraceId: $parentTraceId,
            parametersList: [
                new CreateTraceTreeCacheParameters(
                    serviceId: $parent->serviceId,
                    traceId: $parent->traceId,
                    type: $parent->type,
                    status: $parent->status,
                    duration: $parent->duration,
                    memory: $parent->memory,
                    cpu: $parent->cpu,
                    loggedAt: $parent->loggedAt,
                ),
            ]
        );

        $tree = [
            $parentTree,
        ];

        /** @var TraceTreeMapObject $map */
        $map = [
            $parentTree->traceId => $parentTree,
        ];

        $childIds = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId(
            traceId: $parentTraceId
        );

        foreach (array_chunk(array: $childIds, length: 500) as $childIdsChunk) {
            $foundTraces = $this->traceRepository->findByTraceIds(
                traceIds: $childIdsChunk
            );

            /** @var CreateTraceTreeCacheParameters[] $cacheParametersList */
            $cacheParametersList = [];

            foreach ($foundTraces as $foundTrace) {
                $map[$foundTrace->traceId] ??= new TraceTreeMapObject(
                    traceId: $foundTrace->traceId,
                    children: [],
                    loggedAt: null
                );

                $map[$foundTrace->traceId]->loggedAt = $foundTrace->loggedAt->toDateTimeString('microsecond');

                $map[$foundTrace->parentTraceId] ??= new TraceTreeMapObject(
                    traceId: $foundTrace->parentTraceId,
                    children: [],
                    loggedAt: null
                );

                $map[$foundTrace->parentTraceId]->children[] = $map[$foundTrace->traceId];

                $cacheParametersList[] = new CreateTraceTreeCacheParameters(
                    serviceId: $foundTrace->serviceId,
                    traceId: $foundTrace->traceId,
                    type: $foundTrace->type,
                    status: $foundTrace->status,
                    duration: $foundTrace->duration,
                    memory: $foundTrace->memory,
                    cpu: $foundTrace->cpu,
                    loggedAt: $foundTrace->loggedAt,
                );
            }

            $this->traceTreeCacheRepository->createMany(
                parentTraceId: $parentTraceId,
                parametersList: $cacheParametersList
            );
        }

        $treeOrder = 0;
        $depth     = 0;
        $depths    = [];

        $this->freshDepthRecursive(
            parentTraceId: $parentTraceId,
            order: $treeOrder,
            depth: $depth,
            tree: $tree,
            depths: $depths,
        );

        if (count($depths) > 0) {
            $this->traceTreeCacheRepository->updateDepths(
                parentTraceId: $parentTraceId,
                depths: $depths
            );
        }
    }

    /**
     * @param TraceTreeMapObject[] $tree
     * @param array<string, TraceTreeMapDepthObject> $depths
     */
    private function freshDepthRecursive(
        string $parentTraceId,
        int &$order,
        int $depth,
        array &$tree,
        array &$depths
    ): void {
        ++$depth;

        $tree = array_filter(
            $tree,
            static fn(TraceTreeMapObject $trace) => !is_null($trace->loggedAt),
        );

        usort($tree, static function (TraceTreeMapObject $a, TraceTreeMapObject $b) {
            $aLoggedAt = Carbon::parse($a->loggedAt);
            $bLoggedAt = Carbon::parse($b->loggedAt);

            if ($aLoggedAt->eq($bLoggedAt)) {
                return 0;
            }

            return $aLoggedAt->gt($bLoggedAt) ? 1 : -1;
        });

        while ($branch = array_shift($tree)) {
            ++$order;

            $depths[$branch->traceId] = new TraceTreeMapDepthObject(
                order: $order,
                depth: $depth,
            );

            if (count($depths) > 1000) {
                $this->traceTreeCacheRepository->updateDepths(
                    parentTraceId: $parentTraceId,
                    depths: $depths
                );

                $depths = [];
            }

            $this->freshDepthRecursive(
                parentTraceId: $parentTraceId,
                order: $order,
                depth: $depth,
                tree: $branch->children,
                depths: $depths
            );
        }
    }
}
