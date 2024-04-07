<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\TraceCollector\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class FreshTraceTreeAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function handle(): void
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
