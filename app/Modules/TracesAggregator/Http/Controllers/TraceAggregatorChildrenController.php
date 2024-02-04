<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\TraceChildrenFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceChildrenSortParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorChildrenIndexRequest;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorChildItemsResponse;
use App\Modules\TracesAggregator\Repositories\TraceChildrenRepository;

readonly class TraceAggregatorChildrenController
{
    public function __construct(
        private TraceChildrenRepository $repository
    ) {
    }

    public function index(
        string $parentTraceId,
        TraceAggregatorChildrenIndexRequest $request
    ): TraceAggregatorChildItemsResponse {
        $validated = $request->validated();

        $children = $this->repository->find(
            new TraceChildrenFindParameters(
                parentTraceId: $parentTraceId,
                page: $validated['page'] ?? 1,
                types: $validated['types'] ?? [],
                loggingPeriod: PeriodParameters::fromStringValues(
                    from: $validated['logging_from'] ?? null,
                    to: $validated['logging_to'] ?? null,
                ),
                sort: array_map(
                    fn(array $sortItem) => TraceChildrenSortParameters::fromStringValues(
                        $sortItem['field'] ?? null,
                        $sortItem['direction'] ?? null
                    ),
                    $validated['sort'] ?? []
                ),
            )
        );

        return new TraceAggregatorChildItemsResponse($children);
    }
}
