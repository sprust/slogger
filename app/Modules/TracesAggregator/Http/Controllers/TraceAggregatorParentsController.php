<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindStatusesParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindTagsParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindTypesParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsSortParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorFindStatusesRequest;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorFindTagsRequest;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorFindTypesRequest;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorParentsIndexRequest;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorParentItemsResponse;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorStringValueResponse;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorTraceDetailResponse;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepositoryInterface;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceAggregatorParentsController
{
    use TraceAggregatorParentsControllerTrait;

    public function __construct(
        private TraceParentsRepositoryInterface $repository
    ) {
    }

    public function index(TraceAggregatorParentsIndexRequest $request): TraceAggregatorParentItemsResponse
    {
        $validated = $request->validated();

        $parents = $this->repository->findParents(
            new TraceParentsFindParameters(
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
                    fn(array $sortItem) => TraceParentsSortParameters::fromStringValues(
                        $sortItem['field'],
                        $sortItem['direction']
                    ),
                    $validated['sort'] ?? []
                ),
            )
        );

        return new TraceAggregatorParentItemsResponse($parents);
    }

    public function show(string $traceId): TraceAggregatorTraceDetailResponse
    {
        $traceObject = $this->repository->findByTraceId($traceId);

        abort_if(!$traceObject, Response::HTTP_NOT_FOUND);

        return new TraceAggregatorTraceDetailResponse($traceObject);
    }

    #[OaListItemTypeAttribute(TraceAggregatorStringValueResponse::class)]
    public function types(TraceAggregatorFindTypesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceAggregatorStringValueResponse::collection(
            $this->repository->findTypes(
                new TraceParentsFindTypesParameters(
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

    #[OaListItemTypeAttribute(TraceAggregatorStringValueResponse::class)]
    public function tags(TraceAggregatorFindTagsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceAggregatorStringValueResponse::collection(
            $this->repository->findTags(
                new TraceParentsFindTagsParameters(
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

    #[OaListItemTypeAttribute(TraceAggregatorStringValueResponse::class)]
    public function statuses(TraceAggregatorFindStatusesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceAggregatorStringValueResponse::collection(
            $this->repository->findStatuses(
                new TraceParentsFindStatusesParameters(
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
