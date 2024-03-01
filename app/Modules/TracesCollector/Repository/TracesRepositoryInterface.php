<?php

namespace App\Modules\TracesCollector\Repository;

use App\Modules\TracesCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParametersList;

interface TracesRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): int;
}
