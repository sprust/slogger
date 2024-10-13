<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTagsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceContentRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;

readonly class FindTagsAction implements FindTagsActionInterface
{
    public function __construct(
        private TraceContentRepositoryInterface $traceContentRepository,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
    }

    public function handle(TraceFindTagsParameters $parameters): array
    {
        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
            tags: $parameters->text ? [$parameters->text] : [],
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
        );

        return $this->traceContentRepository->findTags(
            serviceIds: $parameters->serviceIds,
            text: $parameters->text,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
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
