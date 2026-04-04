<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentObjects;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentResultObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeServiceObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use SConcur\WaitGroup;

readonly class FindTraceTreeContentAction
{
    public function __construct(
        private TraceTreeRepository $traceTreeRepository,
        private FindTraceServicesAction $findTraceServicesAction,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $traceId, bool $isChild): ?TraceTreeContentResultObject
    {
        $rootTraceId = $isChild
            ? $traceId
            : $this->traceTreeRepository->findParentTraceId(
                traceId: $traceId
            );

        if (!$rootTraceId) {
            return null;
        }

        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId(
            rootTraceId: $rootTraceId
        );

        if ($state?->status !== TraceTreeCacheStateStatusEnum::Finished) {
            return null;
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

        return new TraceTreeContentResultObject(
            state: $state,
            content: new TraceTreeContentObjects(
                count: $results[$countKey],
                services: $treeServices,
                types: $results[$typesKey],
                tags: $results[$tagsKey],
                statuses: $results[$statusesKey],
            ),
        );
    }
}
