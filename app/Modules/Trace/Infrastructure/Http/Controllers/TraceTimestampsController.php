<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\Actions\MakeMetricIndicatorsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
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

readonly class TraceTimestampsController
{
    public function __construct(
        private FindTraceTimestampsAction $findTraceTimestampsAction,
        private MakeMetricIndicatorsAction $makeMetricIndicatorsAction,
        private DataFilterParameterTransport $dataFilterParameterTransport
    ) {
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     */
    public function index(TraceTimestampsRequest $request): TraceTimestampsResource
    {
        $validated = $request->validated();

        return new TraceTimestampsResource(
            $this->findTraceTimestampsAction->handle(
                new FindTraceTimestampsParameters(
                    timestampPeriod: TraceTimestampPeriodEnum::from(
                        ArrayValueGetter::string($validated, 'timestamp_period')
                    ),
                    timestampStep: TraceTimestampEnum::from(
                        ArrayValueGetter::string($validated, 'timestamp_step')
                    ),
                    fields: array_map(
                        fn(string $indicator) => TraceMetricFieldEnum::from($indicator),
                        ArrayValueGetter::arrayStringNull($validated, 'fields') ?? []
                    ),
                    dataFields: ArrayValueGetter::arrayStringNull($validated, 'data_fields'),
                    serviceIds: ArrayValueGetter::arrayIntNull($validated, 'service_ids'),
                    loggedAtTo: ($validated['logging_to'] ?? null)
                        ? new Carbon($validated['logging_to'])
                        : null,
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
            )
        );
    }

    #[OaListItemTypeAttribute(TraceMetricIndicatorEnumResource::class)]
    public function fields(): AnonymousResourceCollection
    {
        return TraceMetricIndicatorEnumResource::collection(
            $this->makeMetricIndicatorsAction->handle()
        );
    }
}
