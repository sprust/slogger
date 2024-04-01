<?php

namespace App\Modules\TraceAggregator\Http\Controllers;

use App\Modules\TraceAggregator\Dto\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Http\Requests\ServiceStatIndexRequest;
use App\Modules\TraceAggregator\Http\Responses\ServiceStatsPaginationResource;
use App\Modules\TraceAggregator\Services\ServiceStatService;

readonly class ServiceStatController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private ServiceStatService $serviceStatService
    ) {
    }

    public function index(ServiceStatIndexRequest $request): ServiceStatsPaginationResource
    {
        $validated = $request->validated();

        $statsPaginationObject = $this->serviceStatService->find(
            new TraceFindParameters(
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? null,
                serviceIds: array_map('intval', $validated['service_ids'] ?? []),
                traceId: $validated['trace_id'] ?? null,
                allTracesInTree: $validated['all_traces_in_tree'] ?? false,
                loggingPeriod: PeriodParameters::fromStringValues(
                    from: $validated['logging_from'] ?? null,
                    to: $validated['logging_to'] ?? null,
                ),
                types: $validated['types'] ?? [],
                tags: $validated['tags'] ?? [],
                statuses: $validated['statuses'] ?? [],
                durationFrom: $validated['duration_from'] ?? null,
                durationTo: $validated['duration_to'] ?? null,
                data: $this->makeDataFilterParameter($validated),
            )
        );

        return new ServiceStatsPaginationResource(
            $statsPaginationObject
        );
    }
}
