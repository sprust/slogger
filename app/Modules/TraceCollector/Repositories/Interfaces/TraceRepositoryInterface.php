<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\TraceCollector\Repositories\Dto\TraceTreeDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;

    /** @return TraceTreeDto[] */
    public function findTree(TraceTreeFindParameters $parameters): array;

    /**
     * @return TraceLoggedAtDto[]
     */
    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array;

    /**
     * @param TraceTimestampMetricObject[] $timestamps
     */
    public function updateTraceTimestamps(string $traceId, array $timestamps): void;
}
