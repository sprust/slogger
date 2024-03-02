<?php

namespace App\Modules\TraceAggregator\Http\Controllers;

use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterBooleanParameters;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterNumericParameters;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterStringParameters;
use App\Modules\TraceAggregator\Dto\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceSortParameters;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\TraceAggregator\Http\Requests\TraceFindStatusesRequest;
use App\Modules\TraceAggregator\Http\Requests\TraceFindTagsRequest;
use App\Modules\TraceAggregator\Http\Requests\TraceFindTypesRequest;
use App\Modules\TraceAggregator\Http\Requests\TraceIndexRequest;
use App\Modules\TraceAggregator\Http\Responses\StringValueResponse;
use App\Modules\TraceAggregator\Http\Responses\TraceDetailResponse;
use App\Modules\TraceAggregator\Http\Responses\TraceItemsResponse;
use App\Modules\TraceAggregator\Repositories\TraceRepositoryInterface;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    public function __construct(
        private TraceRepositoryInterface $repository
    ) {
    }

    public function index(TraceIndexRequest $request): TraceItemsResponse
    {
        $validated = $request->validated();

        $parents = $this->repository->findParents(
            new TraceFindParameters(
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? null,
                traceId: $validated['trace_id'] ?? null,
                allTracesInTree: $validated['all_traces_in_tree'] ?? false,
                loggingPeriod: PeriodParameters::fromStringValues(
                    from: $validated['logging_from'] ?? null,
                    to: $validated['logging_to'] ?? null,
                ),
                types: $validated['types'] ?? [],
                tags: $validated['tags'] ?? [],
                statuses: $validated['statuses'] ?? [],
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

        return new TraceItemsResponse($parents);
    }

    public function show(string $traceId): TraceDetailResponse
    {
        $traceObject = $this->repository->findByTraceId($traceId);

        abort_if(!$traceObject, Response::HTTP_NOT_FOUND);

        return new TraceDetailResponse($traceObject);
    }

    #[OaListItemTypeAttribute(StringValueResponse::class)]
    public function types(TraceFindTypesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResponse::collection(
            $this->repository->findTypes(
                new TraceFindTypesParameters(
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

    #[OaListItemTypeAttribute(StringValueResponse::class)]
    public function tags(TraceFindTagsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResponse::collection(
            $this->repository->findTags(
                new TraceFindTagsParameters(
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

    #[OaListItemTypeAttribute(StringValueResponse::class)]
    public function statuses(TraceFindStatusesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return StringValueResponse::collection(
            $this->repository->findStatuses(
                new TraceFindStatusesParameters(
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

    protected function makeDataFilterParameter(array $validated): TraceDataFilterParameters
    {
        return new TraceDataFilterParameters(
            filter: array_map(
                fn(array $filterItem) => new TraceDataFilterItemParameters(
                    field: $filterItem['field'],
                    null: array_key_exists('null', $filterItem)
                        ? $filterItem['null']
                        : null,
                    numeric: array_key_exists('numeric', $filterItem)
                        ? new TraceDataFilterNumericParameters(
                            value: $filterItem['numeric']['value'],
                            comp: TraceDataFilterCompNumericTypeEnum::from($filterItem['numeric']['comp']),
                        )
                        : null,
                    string: array_key_exists('string', $filterItem)
                        ? new TraceDataFilterStringParameters(
                            value: $filterItem['string']['value'],
                            comp: TraceDataFilterCompStringTypeEnum::from($filterItem['string']['comp']),
                        )
                        : null,
                    boolean: array_key_exists('boolean', $filterItem)
                        ? new TraceDataFilterBooleanParameters(
                            value: $filterItem['boolean']['value']
                        )
                        : null
                ),
                $validated['data']['filter'] ?? []
            ),
            fields: $validated['data']['fields'] ?? [],
        );
    }
}
