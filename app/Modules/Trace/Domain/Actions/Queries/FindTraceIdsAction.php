<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Parameters\FindTraceIdsParameters;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceIdsAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    /**
     * @return string[]
     */
    public function handle(FindTraceIdsParameters $parameters): array
    {
        return $this->traceRepository->findTraceIds(
            limit: $parameters->limit,
            loggedAtTo: $parameters->loggedAtTo,
            type: $parameters->type,
            excludedTypes: $parameters->excludedTypes
        );
    }
}
