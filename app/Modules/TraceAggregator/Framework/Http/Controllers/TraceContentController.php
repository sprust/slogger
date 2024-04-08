<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindStatusesAction;
use App\Modules\TraceAggregator\Domain\Actions\FindTagsAction;
use App\Modules\TraceAggregator\Domain\Actions\FindTypesAction;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\TraceAggregator\Framework\Http\Requests\TraceFindStatusesRequest;
use App\Modules\TraceAggregator\Framework\Http\Requests\TraceFindTagsRequest;
use App\Modules\TraceAggregator\Framework\Http\Requests\TraceFindTypesRequest;
use App\Modules\TraceAggregator\Framework\Http\Resources\StringValueResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceContentController
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private FindTypesAction $findTypesAction,
        private FindTagsAction $findTagsAction,
        private FindStatusesAction $findStatusesAction,
    ) {
    }

    #[OaListItemTypeAttribute(StringValueResource::class)]
    public function types(TraceFindTypesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResource::collection(
            $this->findTypesAction->handle(
                new TraceFindTypesParameters(
                    serviceIds: array_map('intval', $validated['service_ids'] ?? []),
                    text: $validated['text'] ?? null,
                    loggingPeriod: PeriodParameters::fromStringValues(
                        from: $validated['logging_from'] ?? null,
                        to: $validated['logging_to'] ?? null,
                    ),
                    data: $this->makeDataFilterParameter($validated),
                )
            )
        );
    }

    #[OaListItemTypeAttribute(StringValueResource::class)]
    public function tags(TraceFindTagsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResource::collection(
            $this->findTagsAction->handle(
                new TraceFindTagsParameters(
                    serviceIds: array_map('intval', $validated['service_ids'] ?? []),
                    text: $validated['text'] ?? null,
                    loggingPeriod: PeriodParameters::fromStringValues(
                        from: $validated['logging_from'] ?? null,
                        to: $validated['logging_to'] ?? null,
                    ),
                    types: $validated['types'] ?? [],
                    data: $this->makeDataFilterParameter($validated),
                )
            )
        );
    }

    #[OaListItemTypeAttribute(StringValueResource::class)]
    public function statuses(TraceFindStatusesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResource::collection(
            $this->findStatusesAction->handle(
                new TraceFindStatusesParameters(
                    serviceIds: array_map('intval', $validated['service_ids'] ?? []),
                    text: $validated['text'] ?? null,
                    loggingPeriod: PeriodParameters::fromStringValues(
                        from: $validated['logging_from'] ?? null,
                        to: $validated['logging_to'] ?? null,
                    ),
                    types: $validated['types'] ?? [],
                    tags: $validated['tags'] ?? [],
                    data: $this->makeDataFilterParameter($validated),
                )
            )
        );
    }
}
