<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Repositories\TraceContentRepository;

readonly class FindTagsAction
{
    public function __construct(
        private TraceContentRepository $traceContentRepository,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
    }

    /**
     * @return TraceStringFieldObject[]
     */
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
