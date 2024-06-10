<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTraceTreesByTraceIdsActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class DeleteTraceTreesByTraceIdsAction implements DeleteTraceTreesByTraceIdsActionInterface
{
    public function __construct(
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function handle(array $ids): void
    {
        $this->traceTreeRepository->deleteByTraceIds(ids: $ids);
    }
}
