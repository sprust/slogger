<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteCollectionsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use Illuminate\Support\Carbon;

readonly class DeleteCollectionsAction implements DeleteCollectionsActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    public function handle(Carbon $loggedAtTo): DeletedTracesObject
    {
        return $this->traceRepository->deleteCollections(
            loggedAtTo: $loggedAtTo
        );
    }
}
