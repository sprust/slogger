<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;

interface FindTraceTimestampsActionInterface
{
    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     */
    public function handle(FindTraceTimestampsParameters $parameters): TraceTimestampsObjects;
}
