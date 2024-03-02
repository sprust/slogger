<?php

namespace App\Modules\TraceAggregator\Services;

use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceAggregator\Repositories\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepositoryInterface;

readonly class TraceTreeService
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function fresh(): void
    {
        $to = now();

        $this->traceTreeRepository->deleteMany(
            new TraceTreeDeleteManyParameters(
                to: $to
            )
        );

        $page = 1;

        while (true) {
            $trees = $this->traceRepository->findTree(
                new TraceTreeFindParameters(
                    page: $page,
                    to: $to
                )
            );

            if (!count($trees)) {
                break;
            }

            $this->traceTreeRepository->insertMany(
                array_map(
                    fn(TraceTreeShortObject $traceTreeObject) => new TraceTreeInsertParameters(
                        traceId: $traceTreeObject->traceId,
                        parentTraceId: $traceTreeObject->parentTraceId,
                        loggedAt: $traceTreeObject->loggedAt
                    ),
                    $trees
                )
            );

            ++$page;
        }
    }
}
