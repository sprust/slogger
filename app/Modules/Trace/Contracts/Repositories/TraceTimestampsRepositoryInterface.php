<?php

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampsListDto;
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
        TraceTimestampEnum $timestamp,
        array $fields,
        ?array $dataFields = null,
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
    ): TraceTimestampsListDto;
}
