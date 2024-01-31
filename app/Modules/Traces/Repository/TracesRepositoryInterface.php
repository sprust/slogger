<?php

namespace App\Modules\Traces\Repository;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParametersList;

interface TracesRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;

    public function updateMany(TraceUpdateParametersList $parametersList): void;
}
