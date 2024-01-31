<?php

namespace App\Modules\Traces\Services;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\Traces\Jobs\TraceCreateJob;
use App\Modules\Traces\Jobs\TraceUpdateJob;

readonly class TracesServiceQueueDispatcher
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        dispatch(new TraceCreateJob($parametersList));
    }

    public function updateMany(TraceUpdateParametersList $parametersList): void
    {
        dispatch(new TraceUpdateJob($parametersList));
    }
}
