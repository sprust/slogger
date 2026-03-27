<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceAdminStoreRepository;

readonly class DeleteTraceAdminStoreAction
{
    public function __construct(
        private TraceAdminStoreRepository $traceAdminStoreRepository
    ) {
    }

    public function handle(string $id): bool
    {
        return $this->traceAdminStoreRepository->delete($id);
    }
}
