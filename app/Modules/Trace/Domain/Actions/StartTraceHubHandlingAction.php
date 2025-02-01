<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Contracts\Actions\StartTraceHubHandlingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceHubRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Dto\Trace\TraceHubDto;

readonly class StartTraceHubHandlingAction implements StartTraceHubHandlingActionInterface
{
    public function __construct(
        private TraceHubRepositoryInterface $traceHubRepository,
        private TraceRepositoryInterface $traceRepository,
    ) {
    }

    public function handle(): void
    {
        $insertedCount = 0;
        $skippedCount  = 0;

        while (true) { // TODO: stop signal feature
            $traces = $this->traceHubRepository->findForHandling(
                page: 1,
                perPage: 20,
                deadTimeLine: now()->subMinutes(50) // TODO: move to config?
            );

            $count = count($traces);

            if (!$count) {
                continue;
            }

            $traceIds = array_map(static fn(TraceHubDto $trace) => $trace->traceId, $traces);

            $traces = array_filter(
                $traces,
                static fn(TraceHubDto $trace) => $trace->inserted
            );

            $insertedCount += $count;
            $skippedCount  += ($count - count($traces));

            $this->traceRepository->createManyByHubs(
                traceHubs: $traces
            );

            $deletedCount = $this->traceHubRepository->delete(
                traceIds: $traceIds
            );

            dump("ins: $insertedCount, sk: $skippedCount, del: $deletedCount"); // TODO
        }
    }
}
