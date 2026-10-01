<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use Illuminate\Support\Carbon;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindTraceGroupsAction
{
    public function __construct(
        private TraceGroupsRepository $traceGroupsRepository
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindGroupsParameters $parameters): TraceGroupsObject
    {
        $loggedAtFrom = $parameters->loggingPeriod->from ?? Carbon::now();
        $loggedAtTo   = $parameters->loggingPeriod->to ?? Carbon::now();

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
            durationTo: $parameters->durationTo,
            data: $parameters->data
        );

        return new TraceGroupsObject(
            items: array_slice($groups, 0, $parameters->limit),
            truncated: count($groups) > $parameters->limit
        );
    }
}
