<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeFilteredObject;
use App\Modules\Trace\Parameters\TraceTreeFilterParameters;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeFilteredAction;

readonly class FindTraceTreeFilteredByAdminUseCase
{
    public function __construct(private FindTraceTreeFilteredAction $findTraceTreeFilteredAction)
    {
    }

    public function handle(string $rootTraceId, TraceTreeFilterParameters $parameters): TraceTreeFilteredObject
    {
        return $this->findTraceTreeFilteredAction->handle(
            rootTraceId: $rootTraceId,
            parameters: $parameters,
        );
    }
}
