<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Mcp\Entities\Trace\TraceDataRangeObject;
use App\Modules\Mcp\Transports\TraceObjectTransport;

readonly class FindMcpDataRangeAction
{
    public function __construct(private FindTraceDataRangeAction $findTraceDataRangeAction, private TraceObjectTransport $traceObjectTransport)
    {
    }

    public function handle(): TraceDataRangeObject
    {
        $result = $this->findTraceDataRangeAction->handle();

        return $this->traceObjectTransport->fromTraceDataRangeObject($result);
    }
}
