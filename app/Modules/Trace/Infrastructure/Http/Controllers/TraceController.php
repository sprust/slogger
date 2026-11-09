<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\FindTraceDetailByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTracesByAdminUseCase;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceDetailResource;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceItemsResource;
use App\Modules\Trace\Parameters\PeriodParameters;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class TraceController
{
    public function __construct(
        private FindTracesByAdminUseCase $findTracesByAdminUseCase,
        private FindTraceDetailByAdminUseCase $findTraceDetailByAdminUseCase,
        private DataFilterParameterTransport $dataFilterParameterTransport
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function index(TraceIndexRequest $request): TraceItemsResource
    {
        $validated     = $request->validated();
        $loggingPeriod = PeriodParameters::fromStringValues(
            fromPreset: ArrayValueGetter::stringNull(
                data: $validated,
                key: 'logging_from_preset',
            ),
            from: ArrayValueGetter::stringNull(
                data: $validated,
                key: 'logging_from',
            ),
            to: ArrayValueGetter::stringNull(
                data: $validated,
                key: 'logging_to',
            ),
        );
        $traces = $this->findTracesByAdminUseCase->handle(
            new TraceFindParameters(
                page: ArrayValueGetter::intNull(
                    data: $validated,
                    key: 'page',
                ) ?? 1,
                perPage: ArrayValueGetter::intNull(
                    data: $validated,
                    key: 'per_page',
                ) ?? null,
                serviceIds: ArrayValueGetter::arrayIntNull(
                    data: $validated,
                    key: 'service_ids',
                ) ?? [],
                traceId: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'trace_id',
                ),
                allTracesInTree: ArrayValueGetter::boolNull(
                    data: $validated,
                    key: 'all_traces_in_tree',
                ) ?? false,
                loggingPeriod: $loggingPeriod,
                types: ArrayValueGetter::arrayStringNull(
                    data: $validated,
                    key: 'types',
                ) ?? [],
                tags: ArrayValueGetter::arrayStringNull(
                    data: $validated,
                    key: 'tags',
                ) ?? [],
                statuses: ArrayValueGetter::arrayStringNull(
                    data: $validated,
                    key: 'statuses',
                ) ?? [],
                durationFrom: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'duration_from',
                ),
                durationTo: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'duration_to',
                ),
                memoryFrom: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'memory_from',
                ),
                memoryTo: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'memory_to',
                ),
                cpuFrom: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'cpu_from',
                ),
                cpuTo: ArrayValueGetter::floatNull(
                    data: $validated,
                    key: 'cpu_to',
                ),
                data: $this->dataFilterParameterTransport->make($validated),
            )
        );

        return new TraceItemsResource($traces);
    }

    public function show(string $traceId): TraceDetailResource
    {
        $traceObject = $this->findTraceDetailByAdminUseCase->handle($traceId);

        return new TraceDetailResource($traceObject);
    }
}
