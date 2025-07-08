<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceServicesObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeMapObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeServiceObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;
use App\Modules\Trace\Parameters\TraceTreeDepthParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use Illuminate\Support\Carbon;
use RuntimeException;
use SParallel\Exceptions\ContextCheckerException;
use SParallel\SParallelWorkers;

readonly class FindTraceTreeAction implements FindTraceTreeActionInterface
{
    private int $perPage;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private TraceTreeCacheRepositoryInterface $traceTreeCacheRepository,
        private SParallelWorkers $parallelWorkers,
        private FindTraceServicesActionInterface $findTraceServicesAction
    ) {
        $this->perPage = 100;
    }

    /**
     * @throws ContextCheckerException
     */
    public function handle(TraceFindTreeParameters $parameters): TraceTreeObjects
    {
        $parentTraceId = $this->traceTreeRepository->findParentTraceId(
            traceId: $parameters->traceId
        );

        if (!$parentTraceId) {
            return new TraceTreeObjects(
                count: 0,
                items: [],
                services: [],
                types: [],
                statuses: [],
            );
        }

        $parent = $this->traceRepository->findOneDetailByTraceId(
            traceId: $parentTraceId
        );

        if (is_null($parent)) {
            return new TraceTreeObjects(
                count: 0,
                items: [],
                services: [],
                types: [],
                statuses: [],
            );
        }

        $needCache = $parameters->fresh
            || !$this->traceTreeCacheRepository->has(
                parentTraceId: $parentTraceId
            );

        if ($needCache) {
            $this->cacheTree(parent: $parent);
        }

        $perPage = $this->perPage;

        $callbacks = [
            'items' => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->paginate(
                    page: $parameters->page,
                    perPage: $perPage,
                    parentTraceId: $parentTraceId
                ),
        ];

        /**
         * @var array{
         *     items: TraceTreeDto[],
         *     services: TraceTreeServiceDto[],
         *     types: TraceTreeStringableObject[],
         *     statuses: TraceTreeStringableObject[],
         *     count: int
         * } $data
         */
        $data = [];

        if ($needCache) {
            $callbacks['services'] = static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findServices(parentTraceId: $parentTraceId);
            $callbacks['types']    = static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findTypes(parentTraceId: $parentTraceId);
            $callbacks['statuses'] = static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findStatuses(parentTraceId: $parentTraceId);
            $callbacks['count']    = static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findCount(parentTraceId: $parentTraceId);
        } else {
            $data['services'] = [];
            $data['types']    = [];
            $data['statuses'] = [];
            $data['count']    = 0;
        }

        $results = $this->parallelWorkers->wait(
            callbacks: $callbacks,
            timeoutSeconds: 25,
            breakAtFirstError: true
        );

        if ($results->hasFailed()) {
            $failed = $results->getFailed();

            throw $failed[array_key_first($failed)]->exception
                ?: new RuntimeException('Failed to get tree data');
        }

        foreach ($results->getResults() as $result) {
            $data[$result->taskKey] = $result->result;
        }

        $services = $this->findServices(
            traceTrees: $data['items'],
            treeServices: $data['services'],
        );

        $traces = [];

        foreach ($data['items'] as $traceDto) {
            /** @var TraceTreeDto $traceDto */

            $service = $services->getById($traceDto->serviceId);

            $traces[] = new TraceTreeObject(
                service: $service
                    ? new TraceServiceObject(
                        id: $service->id,
                        name: $service->name,
                    )
                    : null,
                traceId: $traceDto->traceId,
                parentTraceId: $traceDto->parentTraceId,
                type: $traceDto->type,
                status: $traceDto->status,
                duration: $traceDto->duration,
                memory: $traceDto->memory,
                cpu: $traceDto->cpu,
                loggedAt: $traceDto->loggedAt,
                depth: $traceDto->depth,
            );
        }

        $treeServices = [];

        foreach ($data['services'] as $serviceDto) {
            /** @var TraceTreeServiceObject $serviceDto */

            $service = $services->getById($serviceDto->id);

            $treeServices[] = new TraceTreeServiceObject(
                id: $serviceDto->id,
                name: $service?->name ?: 'UNKNOWN',
                tracesCount: $serviceDto->tracesCount,
            );
        }

        return new TraceTreeObjects(
            count: $data['count'],
            items: $traces,
            services: $treeServices,
            types: $data['types'],
            statuses: $data['statuses'],
        );
    }

    private function cacheTree(TraceDto $parent): void
    {
        // TODO: add tags

        $parentTraceId = $parent->traceId;

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
     * @param TraceTreeMapObject[]                    $tree
     * @param array<string, TraceTreeDepthParameters> $depths
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

            $depths[$branch->traceId] = new TraceTreeDepthParameters(
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

    /**
     * @param TraceTreeDto[]        $traceTrees
     * @param TraceTreeServiceDto[] $treeServices
     */
    private function findServices(array $traceTrees, array $treeServices): TraceServicesObject
    {
        $serviceIds = array_unique(
            [
                ...array_map(
                    fn(TraceTreeDto $item) => $item->serviceId,
                    $traceTrees
                ),
                ...array_map(
                    fn(TraceTreeServiceDto $item) => $item->id,
                    $treeServices
                ),
            ]
        );

        return $this->findTraceServicesAction->handle(
            serviceIds: $serviceIds
        );
    }
}
