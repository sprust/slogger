<?php

namespace App\Modules\TraceAggregator\Http\Controllers;

use App\Modules\TraceAggregator\Dto\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceSortParameters;
use App\Modules\TraceAggregator\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Http\Requests\TraceIndexRequest;
use App\Modules\TraceAggregator\Http\Responses\TraceDetailResponse;
use App\Modules\TraceAggregator\Http\Responses\TraceItemsResponse;
use App\Modules\TraceAggregator\Repositories\TraceRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceRepositoryInterface $repository
    ) {
    }

    public function index(TraceIndexRequest $request): TraceItemsResponse
    {
        $validated = $request->validated();

        $traces = $this->repository->find(
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
                sort: array_map(
                    fn(array $sortItem) => TraceSortParameters::fromStringValues(
                        $sortItem['field'],
                        $sortItem['direction']
                    ),
                    $validated['sort'] ?? []
                ),
            )
        );

        return new TraceItemsResponse($traces);
    }

    public function show(string $traceId): TraceDetailResponse
    {
        $traceObject = $this->repository->findOneByTraceId($traceId);

        abort_if(!$traceObject, Response::HTTP_NOT_FOUND);

        return new TraceDetailResponse($traceObject);
    }
}
