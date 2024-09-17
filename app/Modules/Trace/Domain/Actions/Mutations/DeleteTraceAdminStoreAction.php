<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTraceAdminStoreActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceAdminStoreRepositoryInterface;

readonly class DeleteTraceAdminStoreAction implements DeleteTraceAdminStoreActionInterface
{
    public function __construct(
        private TraceAdminStoreRepositoryInterface $traceAdminStoreRepository
    ) {
    }

    public function handle(string $id): bool
    {
        return $this->traceAdminStoreRepository->delete($id);
    }
}
