<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Trace;

readonly class TraceItemObject
{
    public function __construct(public TraceItemTraceObject $trace)
    {
    }
}
