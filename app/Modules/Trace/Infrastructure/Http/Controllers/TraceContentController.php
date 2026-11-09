<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Domain\UseCases\FindStatusesByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTagsByAdminUseCase;
use App\Modules\Trace\Domain\UseCases\FindTypesByAdminUseCase;
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
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class TraceContentController
{
    public function __construct(private FindTypesByAdminUseCase $findTypesByAdminUseCase, private FindTagsByAdminUseCase $findTagsByAdminUseCase, private FindStatusesByAdminUseCase $findStatusesByAdminUseCase, private DataFilterParameterTransport $dataFilterParameterTransport)
    {
    }

    /**
     * @throws ClickhouseQueryException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function types(TraceFindTypesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findTypesByAdminUseCase->handle(
                new TraceFindTypesParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull(
                        data: $validated,
                        key: 'service_ids',
                    ) ?? [],
                    text: ArrayValueGetter::stringNull(
                        data: $validated,
                        key: 'text',
                    ),
                    loggingPeriod: PeriodParameters::fromStringValues(
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
                    ),
                    tags: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'tags',
                    ) ?? [],
                    statuses: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'statuses',
                    ) ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                )
            )
        );
    }

    /**
     * @throws ClickhouseQueryException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function tags(TraceFindTagsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findTagsByAdminUseCase->handle(
                new TraceFindTagsParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull(
                        data: $validated,
                        key: 'service_ids',
                    ) ?? [],
                    text: ArrayValueGetter::stringNull(
                        data: $validated,
                        key: 'text',
                    ),
                    loggingPeriod: PeriodParameters::fromStringValues(
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
                    ),
                    types: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'types',
                    ) ?? [],
                    statuses: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'statuses',
                    ) ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                )
            )
        );
    }

    /**
     * @throws ClickhouseQueryException
     */
    #[OaListItemTypeAttribute(TraceStringFieldResource::class)]
    public function statuses(TraceFindStatusesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceStringFieldResource::collection(
            $this->findStatusesByAdminUseCase->handle(
                new TraceFindStatusesParameters(
                    serviceIds: ArrayValueGetter::arrayIntNull(
                        data: $validated,
                        key: 'service_ids',
                    ) ?? [],
                    text: ArrayValueGetter::stringNull(
                        data: $validated,
                        key: 'text',
                    ),
                    loggingPeriod: PeriodParameters::fromStringValues(
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
                    ),
                    types: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'types',
                    ) ?? [],
                    tags: ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'tags',
                    ) ?? [],
                    durationFrom: $validated['duration_from'] ?? null,
                    durationTo: $validated['duration_to'] ?? null,
                    memoryFrom: $validated['memory_from'] ?? null,
                    memoryTo: $validated['memory_to'] ?? null,
                    cpuFrom: $validated['cpu_from'] ?? null,
                    cpuTo: $validated['cpu_to'] ?? null,
                    data: $this->dataFilterParameterTransport->make($validated),
                )
            )
        );
    }
}
