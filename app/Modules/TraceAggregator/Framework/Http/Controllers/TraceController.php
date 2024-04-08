<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindTraceDetailAction;
use App\Modules\TraceAggregator\Domain\Actions\FindTracesAction;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceSortParameters;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Framework\Http\Requests\TraceIndexRequest;
use App\Modules\TraceAggregator\Framework\Http\Resources\TraceDetailResource;
use App\Modules\TraceAggregator\Framework\Http\Resources\TraceItemsResource;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private FindTracesAction $findTracesAction,
        private FindTraceDetailAction $findTraceDetailAction
    ) {
    }

    public function index(TraceIndexRequest $request): TraceItemsResource
    {
        $validated = $request->validated();

        $traces = $this->findTracesAction->handle(
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

        return new TraceItemsResource($traces);
    }

    public function show(string $traceId): TraceDetailResource
    {
        $traceObject = $this->findTraceDetailAction->handle($traceId);

        abort_if(!$traceObject, Response::HTTP_NOT_FOUND);

        return new TraceDetailResource($traceObject);
    }
}
