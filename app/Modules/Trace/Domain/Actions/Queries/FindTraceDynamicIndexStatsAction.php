<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;

readonly class FindTraceDynamicIndexStatsAction
{
    public function __construct(
        private TraceRepository $traceRepository,
        private TraceDynamicIndexRepository $traceDynamicIndexRepository
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
