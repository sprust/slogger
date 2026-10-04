<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Repositories\TraceContentRepository;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindStatusesAction
{
    public function __construct(
        private TraceContentRepository $repository
    ) {
    }

    /**
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindStatusesParameters $parameters): array
    {
        return $this->repository->findStatuses(
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
            data: $parameters->data,
        );
    }
}
