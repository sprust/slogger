<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeChildrenAction;

readonly class FindTraceTreeChildrenByAdminUseCase
{
    public function __construct(private FindTraceTreeChildrenAction $findTraceTreeChildrenAction)
    {
    }

    public function handle(string $rootTraceId, ?string $parentTraceId, ?string $cursor, int $limit): TraceTreeChildrenObject
    {
        return $this->findTraceTreeChildrenAction->handle(
            rootTraceId: $rootTraceId,
            parentTraceId: $parentTraceId,
            cursor: $cursor,
            limit: $limit,
        );
    }
}
