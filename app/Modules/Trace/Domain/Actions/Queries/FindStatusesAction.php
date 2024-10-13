<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindStatusesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceContentRepositoryInterface;
use App\Modules\Trace\Infrastructure\Http\Controllers\Traits\MakeDataFilterParameterTrait;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use App\Modules\Trace\Repositories\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Transports\TraceStringFieldTransport;

readonly class FindStatusesAction implements FindStatusesActionInterface
{
    use MakeDataFilterParameterTrait;

    public function __construct(
        private TraceContentRepositoryInterface $repository,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
    }

    public function handle(TraceFindStatusesParameters $parameters): array
    {
        $data = TraceDataFilterTransport::toDtoIfNotNull($parameters->data);

        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: ['stub'],
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $data,
            hasProfiling: $parameters->hasProfiling,
        );

        return array_map(
            fn(TraceStringFieldDto $dto) => TraceStringFieldTransport::toObject($dto),
            $this->repository->findStatuses(
                serviceIds: $parameters->serviceIds,
                text: $parameters->text,
                loggedAtFrom: $parameters->loggingPeriod?->from,
                loggedAtTo: $parameters->loggingPeriod?->to,
                types: $parameters->types,
                tags: $parameters->tags,
                durationFrom: $parameters->durationFrom,
                durationTo: $parameters->durationTo,
                memoryFrom: $parameters->memoryFrom,
                memoryTo: $parameters->memoryTo,
                cpuFrom: $parameters->cpuFrom,
                cpuTo: $parameters->cpuTo,
                data: $data,
                hasProfiling: $parameters->hasProfiling,
            )
        );
    }
}
