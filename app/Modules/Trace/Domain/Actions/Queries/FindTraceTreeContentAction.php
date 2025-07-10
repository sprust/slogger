<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeContentActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeCacheRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentObjects;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeServiceObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use RuntimeException;
use SParallel\Exceptions\ContextCheckerException;
use SParallel\SParallelWorkers;

readonly class FindTraceTreeContentAction implements FindTraceTreeContentActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private SParallelWorkers $parallelWorkers,
        private FindTraceServicesActionInterface $findTraceServicesAction
    ) {
    }

    /**
     * @throws ContextCheckerException
     */
    public function handle(string $traceId): TraceTreeContentObjects
    {
        $parentTraceId = $this->traceTreeRepository->findParentTraceId(
            traceId: $traceId
        );

        if (!$parentTraceId) {
            return new TraceTreeContentObjects(
                count: 0,
                services: [],
                types: [],
                tags: [],
                statuses: [],
            );
        }

        $parent = $this->traceRepository->findOneDetailByTraceId(
            traceId: $parentTraceId
        );

        if (is_null($parent)) {
            return new TraceTreeContentObjects(
                count: 0,
                services: [],
                types: [],
                tags: [],
                statuses: [],
            );
        }

        $callbacks = [
            'services' => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findServices(rootTraceId: $parentTraceId),
            'types'    => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findTypes(rootTraceId: $parentTraceId),
            'tags'     => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findTags(rootTraceId: $parentTraceId),
            'statuses' => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findStatuses(rootTraceId: $parentTraceId),
            'count'    => static fn(TraceTreeCacheRepositoryInterface $repository) => $repository
                ->findCount(rootTraceId: $parentTraceId),
        ];

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

        /**
         * @var array{
         *     services: TraceTreeServiceDto[],
         *     types: TraceTreeStringableObject[],
         *     tags: TraceTreeStringableObject[],
         *     statuses: TraceTreeStringableObject[],
         *     count: int
         * } $data
         */
        $data = [];

        foreach ($results->getResults() as $result) {
            $data[$result->taskKey] = $result->result;
        }

        $services = $this->findTraceServicesAction->handle(
            serviceIds: array_unique(
                array_map(
                    static fn(TraceTreeServiceDto $item) => $item->id,
                    $data['services']
                )
            )
        );

        $treeServices = [];

        foreach ($data['services'] as $serviceDto) {
            /** @var TraceTreeServiceDto $serviceDto */

            $service = $services->getById($serviceDto->id);

            $treeServices[] = new TraceTreeServiceObject(
                id: $serviceDto->id,
                name: $service?->name ?: 'UNKNOWN',
                tracesCount: $serviceDto->tracesCount,
            );
        }

        return new TraceTreeContentObjects(
            count: $data['count'],
            services: $treeServices,
            types: $data['types'],
            tags: $data['tags'],
            statuses: $data['statuses'],
        );
    }
}
