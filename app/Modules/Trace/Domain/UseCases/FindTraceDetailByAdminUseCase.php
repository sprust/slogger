<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDetailAction;

readonly class FindTraceDetailByAdminUseCase
{
    public function __construct(private FindTraceDetailAction $findTraceDetailAction)
    {
    }

    /**
     * @throws ClickhouseQueryException
     * @throws TraceNotFoundException
     */
    public function handle(string $traceId): TraceDetailObject
    {
        return $this->findTraceDetailAction->handle(
            traceId: $traceId,
        ) ?? throw new TraceNotFoundException();
    }
}
