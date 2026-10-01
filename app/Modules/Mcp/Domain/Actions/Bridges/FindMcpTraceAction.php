<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindMcpTraceAction
{
    public function __construct(
        private FindTraceDetailAction $findTraceDetailAction
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(string $traceId): ?TraceDetailObject
    {
        return $this->findTraceDetailAction->handle($traceId);
    }
}
