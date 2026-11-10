<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceTreeCacheStatesAction;

readonly class FindTraceTreeCacheStatesByAdminUseCase
{
    public function __construct(
        private FindTraceTreeCacheStatesAction $findTraceTreeCacheStatesAction
    ) {
    }

    /**
     * @return TraceTreeCacheStateObject[]
     */
    public function handle(int $limit, ?TraceTreeCacheStateStatusEnum $excludeStatus): array
    {
        return $this->findTraceTreeCacheStatesAction->handle(
            limit: $limit,
            excludeStatus: $excludeStatus,
        );
    }
}
