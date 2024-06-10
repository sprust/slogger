<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTracesByTraceIdsActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class DeleteTracesByTraceIdsAction implements DeleteTracesByTraceIdsActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    public function handle(array $ids): void
    {
        $this->traceRepository->deleteByTraceIds(ids: $ids);
    }
}
