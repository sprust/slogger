<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;

readonly class FindMcpDataRangeAction
{
    public function __construct(
        private FindTraceDataRangeAction $findTraceDataRangeAction
    ) {
    }

    public function handle(): TraceDataRangeObject
    {
        return $this->findTraceDataRangeAction->handle();
    }
}
