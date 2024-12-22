<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTracesActionInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Infrastructure\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDetailResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceItemsResource;
use App\Modules\Trace\Infrastructure\Http\Services\TraceDynamicIndexingActionService;
use App\Modules\Trace\Parameters\PeriodParameters;
use App\Modules\Trace\Parameters\TraceFindParameters;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private FindTracesActionInterface $findTracesAction,
        private FindTraceDetailActionInterface $findTraceDetailAction,
        private TraceDynamicIndexingActionService $traceDynamicIndexingActionService
    ) {
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     */
    public function index(TraceIndexRequest $request): TraceItemsResource
    {
        $validated = $request->validated();

        $loggingPeriod = PeriodParameters::fromStringValues(
            from: ArrayValueGetter::stringNull($validated, 'logging_from'),
            to: ArrayValueGetter::stringNull($validated, 'logging_to'),
        );

        /** @var TraceItemObjects $traces */
        $traces = $this->traceDynamicIndexingActionService->handle(
            fn() => $this->findTracesAction->handle(
                new TraceFindParameters(
                    page: ArrayValueGetter::intNull($validated, 'page') ?? 1,
                    perPage: ArrayValueGetter::intNull($validated, 'per_page') ?? null,
                    serviceIds: ArrayValueGetter::arrayIntNull($validated, 'service_ids') ?? [],
                    traceId: ArrayValueGetter::stringNull($validated, 'trace_id'),
                    allTracesInTree: ArrayValueGetter::boolNull($validated, 'all_traces_in_tree') ?? false,
                    loggingPeriod: $loggingPeriod,
                    types: ArrayValueGetter::arrayStringNull($validated, 'types') ?? [],
                    tags: ArrayValueGetter::arrayStringNull($validated, 'tags') ?? [],
                    statuses: ArrayValueGetter::arrayStringNull($validated, 'statuses') ?? [],
                    durationFrom: ArrayValueGetter::floatNull($validated, 'duration_from'),
                    durationTo: ArrayValueGetter::floatNull($validated, 'duration_to'),
                    memoryFrom: ArrayValueGetter::floatNull($validated, 'memory_from'),
                    memoryTo: ArrayValueGetter::floatNull($validated, 'memory_to'),
                    cpuFrom: ArrayValueGetter::floatNull($validated, 'cpu_from'),
                    cpuTo: ArrayValueGetter::floatNull($validated, 'cpu_to'),
                    data: $this->makeDataFilterParameter($validated),
                    hasProfiling: ArrayValueGetter::boolNull($validated, 'has_profiling')
                )
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
