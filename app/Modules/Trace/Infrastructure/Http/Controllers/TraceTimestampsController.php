<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\MakeMetricIndicatorsActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Infrastructure\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTimestampsRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceMetricIndicatorEnumResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Timestamp\TraceTimestampsResource;
use App\Modules\Trace\Infrastructure\Http\Services\TraceDynamicIndexingActionService;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

readonly class TraceTimestampsController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private FindTraceTimestampsActionInterface $findTraceTimestampsAction,
        private MakeMetricIndicatorsActionInterface $makeMetricIndicatorsAction,
        private TraceDynamicIndexingActionService $traceDynamicIndexingActionService
    ) {
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     */
    public function index(TraceTimestampsRequest $request): TraceTimestampsResource
    {
        $validated = $request->validated();

        return new TraceTimestampsResource(
            $this->traceDynamicIndexingActionService->handle(
                fn() => $this->findTraceTimestampsAction->handle(
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
                        data: $this->makeDataFilterParameter($validated),
                        hasProfiling: ArrayValueGetter::boolNull($validated, 'has_profiling')
                    )
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
