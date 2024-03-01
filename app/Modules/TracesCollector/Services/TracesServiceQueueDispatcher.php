<?php

namespace App\Modules\TracesCollector\Services;

use App\Modules\TracesCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TracesCollector\Jobs\TraceCreateJob;
use App\Modules\TracesCollector\Jobs\TraceUpdateJob;

readonly class TracesServiceQueueDispatcher
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        dispatch(new TraceCreateJob($parametersList));
    }

    public function updateMany(TraceUpdateParametersList $parametersList): void
    {
        // TODO: delay cause laravel-roadrunner queue can't do releasing
        dispatch(new TraceUpdateJob($parametersList))->delay(5);
    }
}
