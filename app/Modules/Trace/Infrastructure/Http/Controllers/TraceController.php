<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDetailResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceItemsResource;
use App\Modules\Trace\Parameters\PeriodParameters;
use App\Modules\Trace\Parameters\TraceFindParameters;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    public function __construct(
        private FindTracesAction $findTracesAction,
        private FindTraceDetailAction $findTraceDetailAction,
        private DataFilterParameterTransport $dataFilterParameterTransport
    ) {
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    public function index(TraceIndexRequest $request): TraceItemsResource
    {
        $validated = $request->validated();

        $loggingPeriod = PeriodParameters::fromStringValues(
            fromPreset: ArrayValueGetter::stringNull($validated, 'logging_from_preset'),
            from: ArrayValueGetter::stringNull($validated, 'logging_from'),
            to: ArrayValueGetter::stringNull($validated, 'logging_to'),
        );

        $traces = $this->findTracesAction->handle(
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
                data: $this->dataFilterParameterTransport->make($validated),
                hasProfiling: ArrayValueGetter::boolNull($validated, 'has_profiling')
            )
        );

        return new TraceItemsResource($traces);
    }

    public function show(string $traceId): TraceDetailResource
    {
        $traceObject = $this->findTraceDetailAction->handle($traceId);

        if ($traceObject === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return new TraceDetailResource($traceObject);
    }
}
