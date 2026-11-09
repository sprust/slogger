<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Exceptions\TraceNotFoundException;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeAction;

readonly class FindTraceTreeByAdminUseCase
{
    public function __construct(private FindTraceTreeAction $findTraceTreeAction)
    {
    }

    /**
     * @throws ClickhouseQueryException
     * @throws TraceNotFoundException
     */
    public function handle(string $traceId, bool $fresh, bool $isChild): TraceTreeResultObject
    {
        return $this->findTraceTreeAction->handle(
            traceId: $traceId,
            fresh: $fresh,
            isChild: $isChild,
        ) ?? throw new TraceNotFoundException(
            message: 'Trace not found',
        );
    }
}
