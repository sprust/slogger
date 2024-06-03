<?php

namespace App\Modules\Trace\Framework\Http\Services;

use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Framework\Jobs\TraceCreateJob;
use App\Modules\Trace\Framework\Jobs\TraceUpdateJob;

readonly class QueueDispatcher
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        dispatch(new TraceCreateJob($parametersList));
    }

    public function updateMany(TraceUpdateParametersList $parametersList): void
    {
        dispatch(new TraceUpdateJob($parametersList))->delay(5);
    }
}
