<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Models\Traces\Trace;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Query\Builder as MongoDBBuilder;

class TraceQueryBuilder
{
    /**
     * @param int[]|null                $serviceIds
     * @param string[]|null             $traceIds
     * @param string[]                  $types
     * @param string[]                  $tags
     * @param string[]                  $statuses
     * @param string[]|null             $projectFields
     * @param array<string, mixed>|null $customMatch
     *
     * @return array<string, mixed>
     */
    public function makeAggregationPipeline(
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
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        ?array $projectFields = null,
        ?array $customMatch = null
    ): array {
        $match = [];

        if ($serviceIds) {
            $match['sid'] = ['$in' => $serviceIds];
        }

        if ($traceIds) {
            $match['tid'] = ['$in' => $traceIds];
        }

        if ($loggedAtFrom) {
            $match['lat']['$gte'] = new UTCDateTime($loggedAtFrom->clone()->startOfSecond());
        }

        if ($loggedAtTo) {
            $match['lat']['$lte'] = new UTCDateTime($loggedAtTo->clone()->endOfSecond());
        }

        if ($types) {
            $match['tp'] = ['$in' => $types];
        }

        if ($tags) {
            $match['tgs.nm'] = ['$in' => $tags];
        }

        if ($statuses) {
            $match['st'] = ['$in' => $statuses];
        }

        if (!is_null($durationFrom)) {
            $match['dur']['$gte'] = $durationFrom;
        }

        if (!is_null($durationTo)) {
            $match['dur']['$lte'] = $durationTo;
        }

        if (!is_null($memoryFrom)) {
            $match['mem']['$gte'] = $memoryFrom;
        }

        if (!is_null($memoryTo)) {
            $match['mem']['$lte'] = $memoryTo;
        }

        if (!is_null($cpuFrom)) {
            $match['cpu']['$gte'] = $cpuFrom;
        }

        if (!is_null($cpuTo)) {
            $match['cpu']['$lte'] = $cpuTo;
        }

        if (!is_null($hasProfiling)) {
            $match['hpr'] = $hasProfiling;
        }

        if (!is_null($customMatch)) {
            foreach ($customMatch as $key => $value) {
                $match[$key] = $value;
            }
        }

        if ($data) {
            foreach ($data->filter as $filterItem) {
                $field = $filterItem->field;

                if (!is_null($filterItem->null)) {
                    $match[$field] = $filterItem->null ? ['$exists' => false] : ['$exists' => true];

                    continue;
                }

                if (!is_null($filterItem->numeric)) {
                    $match[$field][$filterItem->numeric->comp->value] = $filterItem->numeric->value;

                    continue;
                }

                if (!is_null($filterItem->string)) {
                    $regex         = match ($filterItem->string->comp) {
                        TraceDataFilterCompStringTypeEnum::Con => ".*{$filterItem->string->value}.*",
                        TraceDataFilterCompStringTypeEnum::Starts => "^{$filterItem->string->value}.*",
                        TraceDataFilterCompStringTypeEnum::Ends => ".*{$filterItem->string->value}$",
                        default => $filterItem->string->value,
                    };
                    $match[$field] = ['$regex' => $regex];

                    continue;
                }

                if (!is_null($filterItem->boolean)) {
                    $match[$field] = $filterItem->boolean->value;
                }
            }
        }

        $pipeline = [];

        if (count($match)) {
            $pipeline[] = ['$match' => $match];
        }

        if (!is_null($projectFields) && count($projectFields)) {
            $pipeline[] = [
                '$project' => array_fill_keys($projectFields, 1),
            ];
        }

        return $pipeline;
    }

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
        ?TraceDataFilterParameters $data = null,
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
     * @return array<string, mixed>
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
                $builder->where($field, $filterItem->boolean->value);
            }
        }

        return $builder;
    }
}
