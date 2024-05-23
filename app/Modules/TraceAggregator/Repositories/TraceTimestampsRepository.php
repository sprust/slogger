<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Enums\TraceTimestampTypeEnum;
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
        TraceTimestampTypeEnum $timestampType,
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
        $timestampField = match ($timestampType) {
            TraceTimestampTypeEnum::M => 'm',
            TraceTimestampTypeEnum::D => 'd',
            TraceTimestampTypeEnum::H12 => 'h12',
            TraceTimestampTypeEnum::H4 => 'h4',
            TraceTimestampTypeEnum::H => 'h',
            TraceTimestampTypeEnum::Min30 => 'min30',
            TraceTimestampTypeEnum::Min10 => 'min10',
            TraceTimestampTypeEnum::Min5 => 'min5',
            TraceTimestampTypeEnum::Min => 'min',
            TraceTimestampTypeEnum::S30 => 's30',
            TraceTimestampTypeEnum::S10 => 's10',
            TraceTimestampTypeEnum::S5 => 's5',
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
