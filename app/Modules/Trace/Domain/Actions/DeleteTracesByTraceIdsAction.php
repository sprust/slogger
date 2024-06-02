<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class DeleteTracesByTraceIdsAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function handle(array $ids): void
    {
        $this->traceRepository->deleteByTraceIds(ids: $ids);
    }
}
