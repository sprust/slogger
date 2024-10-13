<?php

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceDetailObject;

interface FindTraceDetailActionInterface
{
    public function handle(string $traceId): ?TraceDetailObject;
}
