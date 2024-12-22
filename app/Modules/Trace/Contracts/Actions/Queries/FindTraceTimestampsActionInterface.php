<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;

interface FindTraceTimestampsActionInterface
{
    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexErrorException
     */
    public function handle(FindTraceTimestampsParameters $parameters): TraceTimestampsObjects;
}
