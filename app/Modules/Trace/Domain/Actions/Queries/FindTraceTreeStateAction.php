<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStateObject;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;

readonly class FindTraceTreeStateAction
{
    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $traceId): ?TraceTreeStateObject
    {
        $rootTraceId = $this->traceTreeRepository->findParentTraceId(
            traceId: $traceId
        );

        if (!$rootTraceId) {
            return null;
        }

        if ($this->traceRepository->findOneDetailByTraceId($rootTraceId) === null) {
            return null;
        }

        return new TraceTreeStateObject(
            rootTraceId: $rootTraceId,
            state: $this->traceTreeCacheStateRepository->findOneByRootTraceId(
                rootTraceId: $rootTraceId
            )
        );
    }
}
