<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;

interface FindTraceTreeActionInterface
{
    public function handle(string $traceId, bool $fresh, int $page): TraceTreeObjects;
}
