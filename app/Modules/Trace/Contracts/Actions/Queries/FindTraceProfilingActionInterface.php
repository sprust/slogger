<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Profiling\ProfilingTreeObject;
use App\Modules\Trace\Parameters\Profilling\TraceFindProfilingParameters;

interface FindTraceProfilingActionInterface
{
    public function handle(TraceFindProfilingParameters $parameters): ?ProfilingTreeObject;
}
