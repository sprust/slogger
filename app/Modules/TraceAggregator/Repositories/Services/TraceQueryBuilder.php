<?php

namespace App\Modules\TraceAggregator\Repositories\Services;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Domain\Enums\TraceDataFilterCompStringTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Query\Builder as MongoDBBuilder;

class TraceQueryBuilder
{
    /**
     * @param int[]         $serviceIds
     * @param string[]|null $traceIds
     * @param string[]      $types
     * @param string[]      $tags
     * @param string[]      $statuses
     *
     * @return Builder|MongoDBBuilder|Trace
     */
    public function make(
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
    ): Builder {
        $builder = Trace::query()
            ->when($serviceIds, fn(Builder $query) => $query->whereIn('serviceId', $serviceIds))
            ->when($traceIds, fn(Builder $query) => $query->whereIn('traceId', $traceIds))
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', new UTCDateTime($loggedAtFrom)))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', new UTCDateTime($loggedAtTo)))
            ->when($types, fn(Builder $query) => $query->whereIn('type', $types))
            ->when($tags, fn(Builder $query) => $query->where('tags', 'all', $tags))
            ->when($statuses, fn(Builder $query) => $query->whereIn('status', $statuses))
            ->when(!is_null($durationFrom), fn(Builder $query) => $query->where('duration', '>=', $durationFrom))
            ->when(!is_null($durationTo), fn(Builder $query) => $query->where('duration', '<=', $durationTo))
            ->when(!is_null($hasProfiling), fn(Builder $query) => $query->where('hasProfiling', $hasProfiling));

        return $this->applyDataFilter($builder, $data?->filter ?? []);
    }

    /**
     * @param TraceDataFilterItemParameters[] $filter
     */
    private function applyDataFilter(Builder $builder, array $filter): Builder
    {
        foreach ($filter as $filterItem) {
            $field = $filterItem->field;

            if (!is_null($filterItem->null)) {
                $filterItem->null
                    ? $builder->whereNull($field)
                    : $builder->whereNotNull($field);

                continue;
            }

            if (!is_null($filterItem->numeric)) {
                $builder->where(
                    column: $field,
                    operator: $filterItem->numeric->comp->value,
                    value: $filterItem->numeric->value
                );

                continue;
            }

            if (!is_null($filterItem->string)) {
                switch ($filterItem->string->comp) {
                    case TraceDataFilterCompStringTypeEnum::Con:
                        $pre  = '%';
                        $post = '%';
                        break;
                    case TraceDataFilterCompStringTypeEnum::Starts:
                        $pre  = '';
                        $post = '%';
                        break;
                    case TraceDataFilterCompStringTypeEnum::Ends:
                        $pre  = '%';
                        $post = '';
                        break;
                    default:
                        $pre  = '';
                        $post = '';
                        break;
                }

                $builder->where(
                    column: $field,
                    operator: 'like',
                    value: "$pre{$filterItem->string->value}$post"
                );

                continue;
            }

            if (!is_null($filterItem->boolean)) {
                $builder->where($field, $filterItem->boolean);
            }
        }

        return $builder;
    }
}
