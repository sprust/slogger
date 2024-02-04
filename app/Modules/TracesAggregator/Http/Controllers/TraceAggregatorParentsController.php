<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsSortParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorParentsIndexRequest;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorParentItemsResponse;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;

readonly class TraceAggregatorParentsController
{
    public function __construct(
        private TraceParentsRepository $repository
    ) {
    }

    public function index(TraceAggregatorParentsIndexRequest $request): TraceAggregatorParentItemsResponse
    {
        $validated = $request->validated();

        $parents = $this->repository->findParents(
            new TraceParentsFindParameters(
                page: $request->page ?? 1,
                types: $validated['types'] ?? [],
                loggingPeriod: PeriodParameters::fromStringValues(
                    from: $validated['logging_from'] ?? null,
                    to: $validated['logging_to'] ?? null,
                ),
                sort: array_map(
                    fn(array $sortItem) => TraceParentsSortParameters::fromStringValues(
                        $sortItem['field'] ?? null,
                        $sortItem['direction'] ?? null
                    ),
                    $validated['sort'] ?? []
                ),
            )
        );

        return new TraceAggregatorParentItemsResponse($parents);
    }
}
