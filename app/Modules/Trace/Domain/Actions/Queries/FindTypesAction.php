<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;
use App\Modules\Trace\Repositories\TraceContentRepository;

readonly class FindTypesAction
{
    public function __construct(
        private TraceContentRepository $traceContentRepository,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
    }

    /**
     * @return TraceStringFieldObject[]
     */
    public function handle(TraceFindTypesParameters $parameters): array
    {
        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: ['stub'],
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
        );

        return $this->traceContentRepository->findTypes(
            serviceIds: $parameters->serviceIds,
            text: $parameters->text,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
        );
    }
}
