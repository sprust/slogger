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
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use SConcur\WaitGroup;

readonly class FindTraceTreeContentAction implements FindTraceTreeContentActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private FindTraceServicesActionInterface $findTraceServicesAction,
        private TraceTreeCacheRepositoryInterface $traceTreeCacheRepository
    ) {
    }

    public function handle(string $traceId, bool $isChild): TraceTreeContentObjects
    {
        $rootTraceId = $isChild
            ? $traceId
            : $this->traceTreeRepository->findParentTraceId(
                traceId: $traceId
            );

        if (!$rootTraceId) {
            return new TraceTreeContentObjects(
                count: 0,
                services: [],
                types: [],
                tags: [],
                statuses: [],
            );
        }

        $rootTrace = $this->traceRepository->findOneDetailByTraceId(
            traceId: $rootTraceId
        );

        if (is_null($rootTrace)) {
            return new TraceTreeContentObjects(
                count: 0,
                services: [],
                types: [],
                tags: [],
                statuses: [],
            );
        }

        $waitGroup = WaitGroup::create();

        $traceTreeCacheRepository = $this->traceTreeCacheRepository;

        $servicesKey = $waitGroup->add(
            static fn() => $traceTreeCacheRepository
                ->findServices(rootTraceId: $rootTraceId)
        );

        $typesKey = $waitGroup->add(
            static fn() => $traceTreeCacheRepository
                ->findTypes(rootTraceId: $rootTraceId)
        );

        $tagsKey = $waitGroup->add(
            static fn() => $traceTreeCacheRepository
                ->findTags(rootTraceId: $rootTraceId)
        );

        $statusesKey = $waitGroup->add(
            static fn() => $traceTreeCacheRepository
                ->findStatuses(rootTraceId: $rootTraceId)
        );

        $countKey = $waitGroup->add(
            static fn() => $traceTreeCacheRepository
                ->findCount(rootTraceId: $rootTraceId)
        );

        $results = $waitGroup->waitResults();

        $services = $this->findTraceServicesAction->handle(
            serviceIds: array_unique(
                array_map(
                    static fn(TraceTreeServiceDto $item) => $item->id,
                    $results[$servicesKey]
                )
            )
        );

        $treeServices = [];

        foreach ($results[$servicesKey] as $serviceDto) {
            /** @var TraceTreeServiceDto $serviceDto */

            $service = $services->getById($serviceDto->id);

            $treeServices[] = new TraceTreeServiceObject(
                id: $serviceDto->id,
                name: $service?->name ?: 'UNKNOWN',
                tracesCount: $serviceDto->tracesCount,
            );
        }

        return new TraceTreeContentObjects(
            count: $results[$countKey],
            services: $treeServices,
            types: $results[$typesKey] ,
            tags: $results[$tagsKey],
            statuses: $results[$statusesKey],
        );
    }
}
