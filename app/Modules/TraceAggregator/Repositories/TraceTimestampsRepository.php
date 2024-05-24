<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Enums\TraceTimestampMetricEnum;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTimestampsDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Services\TraceQueryBuilder;
use Illuminate\Support\Carbon;

readonly class TraceTimestampsRepository implements TraceTimestampsRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function find(
        TraceTimestampMetricEnum $timestampMetric,
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
        ?array $sort = null,
    ): array {
        $timestampField = match ($timestampMetric) {
            TraceTimestampMetricEnum::M => 'm',
            TraceTimestampMetricEnum::D => 'd',
            TraceTimestampMetricEnum::H12 => 'h12',
            TraceTimestampMetricEnum::H4 => 'h4',
            TraceTimestampMetricEnum::H => 'h',
            TraceTimestampMetricEnum::Min30 => 'min30',
            TraceTimestampMetricEnum::Min10 => 'min10',
            TraceTimestampMetricEnum::Min5 => 'min5',
            TraceTimestampMetricEnum::Min => 'min',
            TraceTimestampMetricEnum::S30 => 's30',
            TraceTimestampMetricEnum::S10 => 's10',
            TraceTimestampMetricEnum::S5 => 's5',
        };

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

        $pipeline[] = [
            '$group' => [
                '_id'   => [
                    'timestamp' => "\$timestamps.$timestampField",
                ],
                'count' => [
                    '$sum' => 1,
                ],
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
            $metrics[] = new TraceTimestampsDto(
                timestamp: new Carbon($item->_id->timestamp->toDateTime()),
                count: $item->count,
            );
        }

        return $metrics;
    }
}
