<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDynamicIndexStatsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;

readonly class FindTraceDynamicIndexStatsAction implements FindTraceDynamicIndexStatsActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
    }

    public function handle(): TraceDynamicIndexStatsObject
    {
        $indexesInProcess = $this->traceRepository->getIndexProgressesInfo();

        $stats = $this->traceDynamicIndexRepository->findStats();

        return new TraceDynamicIndexStatsObject(
            inProcessCount: $stats->inProcessCount,
            errorsCount: $stats->errorsCount,
            totalCount: $stats->totalCount,
            indexesInProcess: $indexesInProcess
        );
    }
}
