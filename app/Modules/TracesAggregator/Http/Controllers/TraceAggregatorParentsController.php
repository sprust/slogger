<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterBooleanParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterNumericParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterStringParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsSortParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\TracesAggregator\Http\Requests\TraceAggregatorParentsIndexRequest;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorParentItemsResponse;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepository;

readonly class TraceAggregatorParentsController
{
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
                data: new TraceDataFilterParameters(
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
                ),
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
}
