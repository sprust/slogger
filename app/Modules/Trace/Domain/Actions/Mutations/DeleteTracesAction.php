<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteTracesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexingActionService;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Parameters\DeleteTracesParameters;

readonly class DeleteTracesAction implements DeleteTracesActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceDynamicIndexingActionService $traceDynamicIndexingActionService,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
    }

    /**
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexErrorException
     */
    public function handle(DeleteTracesParameters $parameters): int
    {
        $this->traceDynamicIndexingActionService->handle(
            fn() => $this->traceDynamicIndexInitializer->init(
                traceIds: $parameters->traceIds,
                loggedAtFrom: $parameters->loggedAtFrom,
                loggedAtTo: $parameters->loggedAtTo,
                types: ['stub'],
            )
        );

        return $this->traceRepository->deleteTraces(
            collectionName: $parameters->collectionName,
            traceIds: $parameters->traceIds,
            loggedAtFrom: $parameters->loggedAtFrom,
            loggedAtTo: $parameters->loggedAtTo,
            type: $parameters->type,
            excludedTypes: $parameters->excludedTypes
        );
    }
}
