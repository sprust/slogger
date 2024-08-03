<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTracesActionInterface;
use App\Modules\Trace\Domain\Entities\Parameters\DeleteTracesParameters;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class DeleteTracesAction implements DeleteTracesActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    public function handle(DeleteTracesParameters $parameters): int
    {
        return $this->traceRepository->deleteTraces(
            loggedAtTo: $parameters->loggedAtTo,
            type: $parameters->type,
            excludedTypes: $parameters->excludedTypes
        );
    }
}
