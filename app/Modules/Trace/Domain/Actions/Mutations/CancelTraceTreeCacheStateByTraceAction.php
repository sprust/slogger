<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Repositories\TraceTreeRepository;

readonly class CancelTraceTreeCacheStateByTraceAction
{
    public function __construct(
        private TraceTreeRepository $traceTreeRepository,
        private CancelTraceTreeCacheStateAction $cancelTraceTreeCacheStateAction,
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

        return $this->cancelTraceTreeCacheStateAction->handle($rootTraceId);
    }
}
