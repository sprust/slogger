<?php

namespace App\Modules\TraceCollector\Services;

use App\Modules\TraceCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Jobs\TraceCreateJob;
use App\Modules\TraceCollector\Jobs\TraceUpdateJob;

readonly class QueueDispatcher
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
