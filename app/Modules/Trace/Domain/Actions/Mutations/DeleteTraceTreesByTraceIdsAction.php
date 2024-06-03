<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class DeleteTraceTreesByTraceIdsAction
{
    public function __construct(
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function handle(array $ids): void
    {
        $this->traceTreeRepository->deleteByTraceIds(ids: $ids);
    }
}
