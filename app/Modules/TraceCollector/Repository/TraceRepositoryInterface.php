<?php

namespace App\Modules\TraceCollector\Repository;

use App\Modules\TraceCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Dto\Parameters\TraceUpdateParametersList;

interface TraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;
}
