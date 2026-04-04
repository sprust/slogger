<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;

readonly class CancelTraceTreeCacheBuildAction
{
    public function __construct(
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $traceId, bool $isChild): ?TraceTreeCacheStateObject
    {
        $rootTraceId = $isChild
            ? $traceId
            : $this->traceTreeRepository->findParentTraceId(
                traceId: $traceId
            );

        if (!$rootTraceId) {
            return null;
        }

        return $this->traceTreeCacheStateRepository->cancel($rootTraceId);
    }
}
