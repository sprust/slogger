<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindByTextParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsSortParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorFindByTextRequest;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorParentsIndexRequest;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorParentItemsResponse;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorStringValueResponse;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceAggregatorParentsController
{
    use TraceAggregatorParentsControllerTrait;

    public function __construct(
        private TraceParentsRepository $repository
    ) {
    }

    public function index(TraceAggregatorParentsIndexRequest $request): TraceAggregatorParentItemsResponse
    {
        $validated = $request->validated();

        $parents = $this->repository->findParents(
            new TraceParentsFindParameters(
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? null,
                loggingPeriod: PeriodParameters::fromStringValues(
                    from: $validated['logging_from'] ?? null,
                    to: $validated['logging_to'] ?? null,
                ),
                types: $validated['types'] ?? [],
                tags: $validated['tags'] ?? [],
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

    #[OaListItemTypeAttribute(TraceAggregatorStringValueResponse::class)]
    public function types(TraceAggregatorFindByTextRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceAggregatorStringValueResponse::collection(
            $this->repository->findTypes(
                new TraceParentsFindByTextParameters(
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
    public function tags(TraceAggregatorFindByTextRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return TraceAggregatorStringValueResponse::collection(
            $this->repository->findTags(
                new TraceParentsFindByTextParameters(
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
}
