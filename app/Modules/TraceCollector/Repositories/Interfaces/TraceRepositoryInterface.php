<?php

namespace App\Modules\TraceCollector\Repositories\Interfaces;

use App\Modules\TraceCollector\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;

interface TraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;


    /** @return TraceTreeShortObject[] */
    public function findTree(TraceTreeFindParameters $parameters): array;
}
