<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Actions\CreateTraceTimestampsAction;
use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use App\Modules\TraceCollector\Repositories\Dto\TraceTimestampMetricDto;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FreshTraceTimestampsAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private CreateTraceTimestampsAction $createTraceTimestampsAction
    ) {
    }

    public function handle(): void
    {
        $to = now('UTC');

        $page = 1;

        while (true) {
            $traceLoggedAtList = $this->traceRepository->findLoggedAtList(
                page: $page,
                perPage: 1000,
                loggedAtTo: $to
            );

            if (empty($traceLoggedAtList)) {
                break;
            }

            ++$page;

            foreach ($traceLoggedAtList as $traceLoggedAt) {
                $this->traceRepository->updateTraceTimestamps(
                    traceId: $traceLoggedAt->traceId,
                    timestamps: array_map(
                        fn(TraceTimestampMetricObject $object) => new TraceTimestampMetricDto(
                            key: $object->key,
                            value: $object->value
                        ),
                        $this->createTraceTimestampsAction->handle(
                            date: $traceLoggedAt->loggedAt
                        )
                    )
                );
            }
        }
    }
}
