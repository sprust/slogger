<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexParallelArraysException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use Illuminate\Support\Carbon;

readonly class FindTraceGroupsAction
{
    public function __construct(
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer,
        private TraceGroupsRepository $traceGroupsRepository
    ) {
    }

    /**
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexParallelArraysException
     */
    public function handle(TraceFindGroupsParameters $parameters): TraceGroupsObject
    {
        $loggedAtFrom = $parameters->loggingPeriod->from ?? Carbon::now();
        $loggedAtTo   = $parameters->loggingPeriod->to ?? Carbon::now();

        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            needLoggedAt: true,
        );

        $groups = $this->traceGroupsRepository->findGroups(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            groupBy: $parameters->groupBy,
            limit: $parameters->limit + 1,
            serviceIds: $parameters->serviceIds,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo
        );

        return new TraceGroupsObject(
            items: array_slice($groups, 0, $parameters->limit),
            truncated: count($groups) > $parameters->limit
        );
    }
}
