<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;

readonly class FindTraceTreeCacheStatesAction
{
    public function __construct(
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    /**
     * @return TraceTreeCacheStateObject[]
     */
    public function handle(
        int $limit,
        ?TraceTreeCacheStateStatusEnum $excludeStatus = null,
    ): array {
        return $this->traceTreeCacheStateRepository->findMany(
            limit: $limit,
            excludeStatus: $excludeStatus
        );
    }
}
