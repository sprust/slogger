<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\MakeMetricIndicatorsByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTraceTimestampsByAdminUseCase;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTimestampsRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceMetricIndicatorEnumResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceTimestampsResource;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class TraceTimestampsController
{
    public function __construct(private FindTraceTimestampsByAdminUseCase $findTraceTimestampsByAdminUseCase, private MakeMetricIndicatorsByAdminUseCase $makeMetricIndicatorsByAdminUseCase, private DataFilterParameterTransport $dataFilterParameterTransport)
    {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function index(TraceTimestampsRequest $request): TraceTimestampsResource
    {
        $validated = $request->validated();

        return new TraceTimestampsResource(
            $this->findTraceTimestampsByAdminUseCase->handle(
                new FindTraceTimestampsParameters(
                    timestampPeriod: TraceTimestampPeriodEnum::from(
                        ArrayValueGetter::string(
                            data: $validated,
                            key: 'timestamp_period',
                        )
                    ),
                    timestampStep: TraceTimestampEnum::from(
                        ArrayValueGetter::string(
                            data: $validated,
                            key: 'timestamp_step',
                        )
                    ),
                    fields: array_map(
                        static fn(string $indicator) => TraceMetricFieldEnum::from($indicator),
                        ArrayValueGetter::arrayStringNull(
                            data: $validated,
                            key: 'fields',
                        ) ?? []
                    ),
                    dataFields: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'data_fields',
                    ),
                    serviceIds: ArrayValueGetter::arrayIntNull(
                        data: $validated,
                        key: 'service_ids',
                    ),
                    loggedAtTo: $validated['logging_to'] ?? null ? (new Carbon(
                        time: $validated['logging_to'],
                    ))->setTimezone('UTC') : null,
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
            )
        );
    }

    #[OaListItemTypeAttribute(TraceMetricIndicatorEnumResource::class)]
    public function fields(): AnonymousResourceCollection
    {
        return TraceMetricIndicatorEnumResource::collection($this->makeMetricIndicatorsByAdminUseCase->handle());
    }
}
