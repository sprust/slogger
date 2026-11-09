<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceSearchScopeObject
{
    /** @param string[]|null $traceIds */
    public function __construct(public ?array $traceIds, public ?string $treeRootTraceId, public bool $traceMissing)
    {
    }
}
