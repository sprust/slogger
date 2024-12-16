<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteOldEmptyCollectionsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;

readonly class DeleteOldEmptyCollectionsAction implements DeleteOldEmptyCollectionsActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    public function handle(): void
    {
        $this->traceRepository->deleteEmptyCollections(
            loggedAtTo: now()->subDay()
        );
    }
}
