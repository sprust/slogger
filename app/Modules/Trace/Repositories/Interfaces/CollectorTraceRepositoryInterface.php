<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use Illuminate\Support\Carbon;

interface CollectorTraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;

    /** @return TraceTreeDto[] */
    public function findTree(int $page = 1, int $perPage = 15, ?Carbon $to = null): array;

    /**
     * @return TraceLoggedAtDto[]
     */
    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array;

    /**
     * @param TraceTimestampMetricDto[] $timestamps
     */
    public function updateTraceTimestamps(string $traceId, array $timestamps): void;
}
