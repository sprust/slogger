<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceDetailObject;

interface FindTraceDetailActionInterface
{
    public function handle(string $traceId): ?TraceDetailObject;
}
