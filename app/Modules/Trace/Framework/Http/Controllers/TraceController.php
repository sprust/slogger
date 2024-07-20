<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Common\Enums\SortDirectionEnum;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceDetailActionInterface;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTracesActionInterface;
use App\Modules\Trace\Domain\Entities\Parameters\PeriodParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceSortParameters;
use App\Modules\Trace\Domain\Exceptions\TraceIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceIndexNotInitException;
use App\Modules\Trace\Framework\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Framework\Http\Requests\TraceIndexRequest;
use App\Modules\Trace\Framework\Http\Resources\TraceDetailResource;
use App\Modules\Trace\Framework\Http\Resources\TraceItemsResource;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceController
{
    use MakeDataFilterParameterTrait;

    private int $indexCreateTimeoutInSeconds;

    public function __construct(
        private FindTracesActionInterface $findTracesAction,
        private FindTraceDetailActionInterface $findTraceDetailAction
    ) {
        $this->indexCreateTimeoutInSeconds = 20;
    }

    /**
     * @throws TraceIndexNotInitException
     */
    public function index(TraceIndexRequest $request): TraceItemsResource
    {
        $validated = $request->validated();

        $loggingPeriod = PeriodParameters::fromStringValues(
            from: $validated['logging_from'] ?? null,
            to: $validated['logging_to'] ?? null,
        );

        $sort = array_filter(
            array_map(
                function (array $sortItem) {
                    $field = $sortItem['field'];

                    if (!$field) {
                        return null;
                    }

                    $direction = $sortItem['direction'];

                    $directionEnum = $direction ? SortDirectionEnum::from($direction) : null;

                    return new TraceSortParameters(
                        field: $field,
                        directionEnum: $directionEnum ?: SortDirectionEnum::Desc
                    );
                },
                $validated['sort'] ?? []
            )
        );

        $start = time();

        while (true) {
            try {
                $traces = $this->findTracesAction->handle(
                    new TraceFindParameters(
                        page: $validated['page'] ?? 1,
                        perPage: $validated['per_page'] ?? null,
                        serviceIds: array_map('intval', $validated['service_ids'] ?? []),
                        traceId: $validated['trace_id'] ?? null,
                        allTracesInTree: $validated['all_traces_in_tree'] ?? false,
                        loggingPeriod: $loggingPeriod,
                        types: $validated['types'] ?? [],
                        tags: $validated['tags'] ?? [],
                        statuses: $validated['statuses'] ?? [],
                        durationFrom: $validated['duration_from'] ?? null,
                        durationTo: $validated['duration_to'] ?? null,
                        memoryFrom: $validated['memory_from'] ?? null,
                        memoryTo: $validated['memory_to'] ?? null,
                        cpuFrom: $validated['cpu_from'] ?? null,
                        cpuTo: $validated['cpu_to'] ?? null,
                        data: $this->makeDataFilterParameter($validated),
                        hasProfiling: ($validated['has_profiling'] ?? null) ?: null,
                        sort: $sort,
                    )
                );
            } catch (TraceIndexInProcessException) {
                abort_if(
                    time() - $start > $this->indexCreateTimeoutInSeconds,
                    "Couldn't init index"
                );

                sleep(1);

                continue;
            }

            break;
        }

        return new TraceItemsResource($traces);
    }

    public function show(string $traceId): TraceDetailResource
    {
        $traceObject = $this->findTraceDetailAction->handle($traceId);

        abort_if(!$traceObject, Response::HTTP_NOT_FOUND);

        return new TraceDetailResource($traceObject);
    }
}
