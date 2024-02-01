<?php

namespace App\Modules\TracesAggregator\Children\Repository;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Children\Dto\Objects\TraceChildObjects;
use App\Modules\TracesAggregator\Children\Dto\Parameters\TraceChildrenFindParameters;
use App\Modules\TracesAggregator\Children\Enums\TraceChildrenSortFieldEnum;
use App\Modules\TracesAggregator\Dto\TraceObject;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;

class TraceChildrenRepository implements TraceChildrenRepositoryInterface
{
    private int $maxPerPage = 30;

    public function find(TraceChildrenFindParameters $parameters): TraceChildObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $loggedAtFrom = $parameters->loggingPeriod?->from;
        $loggedAtTo   = $parameters->loggingPeriod?->to;

        $parentsPaginator = Trace::query()
            ->where('parentTraceId', $parameters->parentTraceId)
            ->when(
                $parameters->types,
                fn(Builder $query) => $query->whereIn('type', $parameters->types)
            )
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', $loggedAtFrom))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', $loggedAtTo))
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $field = match ($sortItem->fieldEnum) {
                            TraceChildrenSortFieldEnum::LoggedAt => 'loggedAt'
                        };

                        $query->orderBy($field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->paginate(
                perPage: $perPage,
                page: $parameters->page
            );

        return new TraceChildObjects(
            items: array_map(
                fn(Trace $trace) => TraceObject::fromModel($trace),
                $parentsPaginator->items()
            ),
            paginationInfo: new PaginationInfoObject(
                total: $parentsPaginator->total(),
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }
}
