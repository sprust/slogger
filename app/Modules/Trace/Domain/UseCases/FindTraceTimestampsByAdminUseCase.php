<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTimestampsAction;

readonly class FindTraceTimestampsByAdminUseCase
{
    public function __construct(
        private FindTraceTimestampsAction $findTraceTimestampsAction
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(FindTraceTimestampsParameters $parameters): TraceTimestampsObjects
    {
        return $this->findTraceTimestampsAction->handle($parameters);
    }
}
