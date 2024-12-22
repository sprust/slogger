<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Services;

use App\Modules\Trace\Infrastructure\Jobs\TraceCreateJob;
use App\Modules\Trace\Infrastructure\Jobs\TraceUpdateJob;
use App\Modules\Trace\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Parameters\TraceUpdateParametersList;

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
