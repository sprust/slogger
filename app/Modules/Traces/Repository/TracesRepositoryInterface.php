<?php

namespace App\Modules\Traces\Repository;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;

interface TracesRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void;
}
