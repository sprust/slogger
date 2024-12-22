<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceDetailObject;

interface FindTraceDetailActionInterface
{
    public function handle(string $traceId): ?TraceDetailObject;
}
