<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Exceptions\TraceTreeStateNotFoundException;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentResultObject;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeContentAction;

readonly class FindTraceTreeContentByAdminUseCase
{
    public function __construct(
        private FindTraceTreeContentAction $findTraceTreeContentAction
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     * @throws TraceTreeStateNotFoundException
     */
    public function handle(string $traceId, bool $isChild): TraceTreeContentResultObject
    {
        return $this->findTraceTreeContentAction->handle(
            traceId: $traceId,
            isChild: $isChild,
        ) ?? throw new TraceTreeStateNotFoundException(
            message: 'Trace or state not found',
        );
    }
}
