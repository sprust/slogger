<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingObject;
use App\Modules\Trace\Domain\Entities\Parameters\Profilling\TraceFindProfilingParameters;

interface FindTraceProfilingActionInterface
{
    public function handle(TraceFindProfilingParameters $parameters): ?ProfilingObject;
}
