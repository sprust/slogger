<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteTraceAdminStoreActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceAdminStoreRepositoryInterface;

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
