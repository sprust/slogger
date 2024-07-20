<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\TraceSortDto;
use Illuminate\Support\Carbon;

class TraceIndexFieldsBuilder
{
    /**
     * @param int[]|null          $serviceIds
     * @param string[]            $types
     * @param string[]            $tags
     * @param string[]            $statuses
     * @param TraceSortDto[]|null $sort
     *
     * @return TraceIndexFieldDto[]
     */
    public function forFind(
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
        ?array $sort = null,
    ): array {
        $fields = [];

        if (!empty($serviceIds)) {
            $fields[] = new TraceIndexFieldDto('serviceId');
        }

        if (!empty($traceIds)) {
            $fields[] = new TraceIndexFieldDto('traceId');
        }

        if (!empty($loggedAtFrom) || !empty($loggedAtTo)) {
            $fields[] = new TraceIndexFieldDto('loggedAt');
        }

        if (!empty($types)) {
            $fields[] = new TraceIndexFieldDto('type');
        }

        if (!empty($tags)) {
            $fields[] = new TraceIndexFieldDto('tags');
        }
        if (!empty($statuses)) {
            $fields[] = new TraceIndexFieldDto('status');
        }

        if (!is_null($durationFrom) || !is_null($durationTo)) {
            $fields[] = new TraceIndexFieldDto('duration');
        }

        if (!is_null($memoryFrom) || !is_null($memoryTo)) {
            $fields[] = new TraceIndexFieldDto('memory');
        }

        if (!is_null($cpuFrom) || !is_null($cpuTo)) {
            $fields[] = new TraceIndexFieldDto('cpu');
        }

        if (!is_null($hasProfiling)) {
            $fields[] = new TraceIndexFieldDto('hasProfiling');
        }

        foreach ($data->filter ?? [] as $dataFilterItem) {
            $fields[] = new TraceIndexFieldDto(
                fieldName: $dataFilterItem->field,
                isText: (bool) $dataFilterItem->string
            );
        }

        foreach ($sort ?? [] as $sortItem) {
            $fields[] = new TraceIndexFieldDto($sortItem->field);
        }

        return $fields;
    }
}
