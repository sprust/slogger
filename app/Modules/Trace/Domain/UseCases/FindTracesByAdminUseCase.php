<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTracesAction;

readonly class FindTracesByAdminUseCase
{
    public function __construct(private FindTracesAction $findTracesAction)
    {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        return $this->findTracesAction->handle(
            parameters: $parameters,
        );
    }
}
