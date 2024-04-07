<?php

namespace App\Modules\TraceCollector\Framework\Http\Services;

use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Framework\Jobs\TraceCreateJob;
use App\Modules\TraceCollector\Framework\Jobs\TraceUpdateJob;

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
