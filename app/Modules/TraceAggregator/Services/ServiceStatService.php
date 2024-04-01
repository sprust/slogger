<?php

namespace App\Modules\TraceAggregator\Services;

use App\Modules\TraceAggregator\Adapters\Dto\ServiceDto as AdapterServiceDto;
use App\Modules\TraceAggregator\Adapters\ServiceAdapter;
use App\Modules\TraceAggregator\Dto\Objects\ServiceStatObject;
use App\Modules\TraceAggregator\Dto\Objects\ServiceStatsPaginationObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Repositories\TraceRepositoryInterface;
use Illuminate\Support\Collection;

readonly class ServiceStatService
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private ServiceAdapter $serviceAdapter
    ) {
    }

    public function find(TraceFindParameters $parameters): ServiceStatsPaginationObject
    {
        $stats = $this->traceRepository->findServiceStat($parameters);

        if (empty($stats->items)) {
            return new ServiceStatsPaginationObject(
                items: [],
                paginationInfo: $stats->paginationInfo
            );
        }

        /** @var Collection<int, AdapterServiceDto> $services */
        $services = collect($this->serviceAdapter->find())
            ->keyBy(fn(AdapterServiceDto $serviceDto) => $serviceDto->id);

        $items = [];

        foreach ($stats->items as $item) {
            $service = $services->get($item->serviceId);

            $items[] = new ServiceStatObject(
                service: new TraceServiceObject(
                    id: $item->serviceId,
                    name: $service?->name ?? 'UNKNOWN',
                ),
                type: $item->type,
                tag: $item->tag,
                status: $item->status,
                count: $item->count,
            );
        }

        return new ServiceStatsPaginationObject(
            items: $items,
            paginationInfo: $stats->paginationInfo
        );
    }
}
