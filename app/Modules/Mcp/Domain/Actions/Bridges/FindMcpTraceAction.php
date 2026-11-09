<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Mcp\Entities\Trace\TraceDetailObject;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Mcp\Transports\TraceObjectTransport;

readonly class FindMcpTraceAction
{
    public function __construct(
        private FindTraceDetailAction $findTraceDetailAction,
        private TraceObjectTransport $traceObjectTransport
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(string $traceId): ?TraceDetailObject
    {
        $result = $this->findTraceDetailAction->handle($traceId);

        return $result === null
            ? null
            : $this->traceObjectTransport->fromTraceDetailObject($result);
    }
}
