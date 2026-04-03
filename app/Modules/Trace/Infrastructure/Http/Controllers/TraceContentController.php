<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceFindStatusesRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceFindTagsRequest;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceFindTypesRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\TraceStringFieldResource;
use App\Modules\Trace\Parameters\PeriodParameters;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceContentController
{
    public function __construct(
        private FindTypesAction $findTypesAction,
        private FindTagsAction $findTagsAction,
        private FindStatusesAction $findStatusesAction,
        private DataFilterParameterTransport $dataFilterParameterTransport
    ) {
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function types(TraceFindTypesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findTypesAction->handle(
                new TraceFindTypesParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull($validated, 'service_ids') ?? [],
                    text: ArrayValueGetter::stringNull($validated, 'text'),
                    loggingPeriod: PeriodParameters::fromStringValues(
                        fromPreset: ArrayValueGetter::stringNull($validated, 'logging_from_preset'),
                        from: ArrayValueGetter::stringNull($validated, 'logging_from'),
                        to: ArrayValueGetter::stringNull($validated, 'logging_to'),
                    ),
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                    hasProfiling: ($validated['has_profiling'] ?? null) ?: null,
                )
            )
        );
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function tags(TraceFindTagsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findTagsAction->handle(
                new TraceFindTagsParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull($validated, 'service_ids') ?? [],
                    text: ArrayValueGetter::stringNull($validated, 'text'),
                    loggingPeriod: PeriodParameters::fromStringValues(
                        fromPreset: ArrayValueGetter::stringNull($validated, 'logging_from_preset'),
                        from: ArrayValueGetter::stringNull($validated, 'logging_from'),
                        to: ArrayValueGetter::stringNull($validated, 'logging_to'),
                    ),
                    types: ArrayValueGetter::arrayStringNull($validated, 'types') ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                    hasProfiling: ($validated['has_profiling'] ?? null) ?: null,
                )
            )
        );
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function statuses(TraceFindStatusesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findStatusesAction->handle(
                new TraceFindStatusesParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull($validated, 'service_ids') ?? [],
                    text: ArrayValueGetter::stringNull($validated, 'text'),
                    loggingPeriod: PeriodParameters::fromStringValues(
                        fromPreset: ArrayValueGetter::stringNull($validated, 'logging_from_preset'),
                        from: ArrayValueGetter::stringNull($validated, 'logging_from'),
                        to: ArrayValueGetter::stringNull($validated, 'logging_to'),
                    ),
                    types: ArrayValueGetter::arrayStringNull($validated, 'types') ?? [],
                    tags: ArrayValueGetter::arrayStringNull($validated, 'tags') ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                    hasProfiling: ($validated['has_profiling'] ?? null) ?: null,
                )
            )
        );
    }
}
