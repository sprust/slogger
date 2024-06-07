<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampsDto;
use Illuminate\Support\Carbon;

interface TraceTimestampsRepositoryInterface
{
    /**
     * @param int[]|null             $serviceIds
     * @param TraceMetricFieldEnum[] $fields
     * @param string[]|null          $dataFields
     * @param string[]               $types
     * @param string[]               $tags
     * @param string[]               $statuses
     *
     * @return TraceTimestampsDto[]
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
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array;
}
