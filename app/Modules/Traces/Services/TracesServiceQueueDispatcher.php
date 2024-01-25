<?php

namespace App\Modules\Traces\Services;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Jobs\TraceCreateJob;

readonly class TracesServiceQueueDispatcher
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        dispatch(new TraceCreateJob($parametersList));
    }
}
