<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsListDto;
use Illuminate\Support\Carbon;

interface TraceTimestampsRepositoryInterface
{
    /**
     * @param int[]|null                            $serviceIds
     * @param TraceMetricFieldsFilterDto[]          $fields
     * @param TraceMetricDataFieldsFilterDto[]|null $dataFields
     * @param string[]                              $types
     * @param string[]                              $tags
     * @param string[]                              $statuses
     */
    public function find(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        TraceTimestampEnum $timestamp,
        array $fields,
        ?array $dataFields = null,
        ?array $serviceIds = null,
        ?array $traceIds = null,
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
    ): TraceTimestampsListDto;
}
