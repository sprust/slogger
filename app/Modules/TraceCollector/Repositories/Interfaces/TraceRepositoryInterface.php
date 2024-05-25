<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricsObject;
use App\Modules\TraceCollector\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Repositories\Dto\TraceLoggedAtDto;
use Illuminate\Support\Carbon;

interface TraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;


    /** @return TraceTreeShortObject[] */
    public function findTree(TraceTreeFindParameters $parameters): array;

    /**
     * @return TraceLoggedAtDto[]
     */
    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array;

    public function updateTraceTimestamps(string $traceId, TraceTimestampMetricsObject $timestamps): void;
}
