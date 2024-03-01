<?php

namespace App\Modules\TracesAggregator\Services;

use App\Modules\TracesAggregator\Dto\Objects\TraceTreeObject;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeInsertParameters;
use App\Modules\TracesAggregator\Repositories\TraceParentsRepositoryInterface;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepositoryInterface;

readonly class TraceTreesService
{
    public function __construct(
        private TraceParentsRepositoryInterface $traceParentsRepository,
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
            $trees = $this->traceParentsRepository->findTree(
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
                    fn(TraceTreeObject $traceTreeObject) => new TraceTreeInsertParameters(
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
