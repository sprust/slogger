<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Enums\TraceMetricIndicatorEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampFieldIndicatorDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

readonly class TraceTimestampsRepository implements TraceTimestampsRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function find(
        TraceTimestampEnum $timestamp,
        array $indicators,
        ?array $dataFieldIndicators = null,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): array {
        $timestampField = $timestamp->value;

        $match = [
            "timestamps.$timestampField" => [
                '$exists' => true,
            ],
        ];

        $mql = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                traceIds: $traceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                tags: $tags,
                statuses: $statuses,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                data: $data,
                hasProfiling: $hasProfiling,
            )
            ->toMql();


        foreach ($mql['find'][0] ?? [] as $key => $value) {
            $match[$key] = $value;
        }

        $pipeline = [
            [
                '$match' => $match,
            ],
        ];

        $groups = [];

        foreach ($indicators as $indicator) {
            if ($indicator === TraceMetricIndicatorEnum::Count) {
                $groups['count'] = [
                    '$sum' => 1,
                ];

                continue;
            }

            if ($indicator === TraceMetricIndicatorEnum::Duration) {
                $groups['duration'] = [
                    '$avg' => '$duration',
                    '$min' => '$duration',
                    '$max' => '$duration',
                ];

                continue;
            }

            if ($indicator === TraceMetricIndicatorEnum::Memory) {
                $groups['memory'] = [
                    '$avg' => '$memory',
                    '$min' => '$memory',
                    '$max' => '$memory',
                ];

                continue;
            }

            if ($indicator === TraceMetricIndicatorEnum::Cpu) {
                $groups['cpu'] = [
                    '$avg' => '$cpu',
                    '$min' => '$cpu',
                    '$max' => '$cpu',
                ];

                continue;
            }

            throw new RuntimeException("Unknown indicator [$indicator->value]");
        }

        foreach ($dataFieldIndicators ?? [] as $dataFieldIndicator) {
            $groups[$dataFieldIndicator] = [
                '$avg' => $dataFieldIndicator,
                '$min' => $dataFieldIndicator,
                '$max' => $dataFieldIndicator,
            ];
        }

        $groupsMatch = [];

        $groupsQuery = [];

        foreach ($groups as $fieldName => $aggregations) {
            $groupIndicators = [];

            foreach ($aggregations as $operator => $operatorValue) {
                $operatorTitle = $fieldName . Str::title(Str::slug($operator));

                $groupIndicators[] = $operatorTitle;

                $groupsQuery[$operatorTitle] = [
                    $operator => $operatorValue,
                ];
            }

            $groupsMatch[$fieldName] = $groupIndicators;
        }

        $pipeline[] = [
            '$group' => [
                '_id' => [
                    'timestamp' => "\$timestamps.$timestampField",
                ],
                ...$groupsQuery,
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                '_id.timestamp' => 1,
            ],
        ];

        $aggregation = Trace::collection()->aggregate($pipeline);

        $metrics = [];

        foreach ($aggregation as $item) {
            $groupIndicatorsDtoList = [];

            foreach ($groupsMatch as $fieldName => $groupIndicators) {
                $groupIndicatorsDtoList[] = new TraceTimestampFieldDto(
                    field: $fieldName,
                    indicators: array_map(
                        fn(string $operatorTitle) => new TraceTimestampFieldIndicatorDto(
                            name: Str::headline($operatorTitle),
                            value: round(is_numeric($item->{$operatorTitle}) ? $item->{$operatorTitle} : 0, 6),
                        ),
                        $groupIndicators
                    )
                );
            }

            $metrics[] = new TraceTimestampsDto(
                timestamp: new Carbon($item->_id->timestamp->toDateTime()),
                indicators: $groupIndicatorsDtoList
            );
        }

        return $metrics;
    }
}
