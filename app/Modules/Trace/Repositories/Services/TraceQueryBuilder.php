<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Models\Traces\Trace;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterItemDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
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
     * @phpstan-ignore-next-line
     * PHPDoc tag 'return' with type
     * App\Models\Traces\Trace|Illuminate\Database\Eloquent\Builder|MongoDB\Laravel\Query\Builder is not subtype
     * of native type Illuminate\Database\Eloquent\Builder
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
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): Builder {
        $builder = Trace::query()
            ->when($serviceIds, fn(Builder $query) => $query->whereIn('sid', $serviceIds))
            ->when($traceIds, fn(Builder $query) => $query->whereIn('tid', $traceIds))
            ->when(
                $loggedAtFrom,
                fn(Builder $query) => $query->where(
                    'lat',
                    '>=',
                    new UTCDateTime($loggedAtFrom->clone()->startOfSecond())
                )
            )
            ->when(
                $loggedAtTo,
                fn(Builder $query) => $query->where(
                    'lat',
                    '<=',
                    new UTCDateTime($loggedAtTo->clone()->endOfSecond())
                )
            )
            ->when($types, fn(Builder $query) => $query->whereIn('tp', $types))
            ->when($tags, fn(Builder $query) => $query->whereIn('tgs.nm', $tags))
            ->when($statuses, fn(Builder $query) => $query->whereIn('st', $statuses))
            ->when(!is_null($durationFrom), fn(Builder $query) => $query->where('dur', '>=', $durationFrom))
            ->when(!is_null($durationTo), fn(Builder $query) => $query->where('dur', '<=', $durationTo))
            ->when(!is_null($memoryFrom), fn(Builder $query) => $query->where('mem', '>=', $memoryFrom))
            ->when(!is_null($memoryTo), fn(Builder $query) => $query->where('mem', '<=', $memoryTo))
            ->when(!is_null($cpuFrom), fn(Builder $query) => $query->where('cpu', '>=', $cpuFrom))
            ->when(!is_null($cpuTo), fn(Builder $query) => $query->where('cpu', '<=', $cpuTo))
            ->when(!is_null($hasProfiling), fn(Builder $query) => $query->where('hpr', $hasProfiling));

        return $this->applyDataFilter($builder, $data?->filter ?? []);
    }

    /**
     * @phpstan-ignore-next-line
     * PHPDoc tag 'param' for parameter $builder with type
     * Illuminate\Database\Eloquent\Builder|MongoDB\Laravel\Query\Builder is not subtype of native type
     * Illuminate\Database\Eloquent\Builder
     *
     * @param Builder|MongoDBBuilder $builder
     *
     * @return array
     */
    public function makeMqlMatchFromBuilder(Builder $builder): array
    {
        $match = [];

        foreach ($builder->toMql()['find'][0] ?? [] as $key => $value) {
            $match[$key] = $value;
        }

        return $match;
    }

    /**
     * @param TraceDataFilterItemDto[] $filter
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
