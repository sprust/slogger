<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceDynamicIndexStatsActionInterface;
use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;

readonly class FindTraceDynamicIndexStatsAction implements FindTraceDynamicIndexStatsActionInterface
{
    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
    }

    public function handle(): TraceDynamicIndexStatsObject
    {
        $stats = $this->traceDynamicIndexRepository->findStats();

        return new TraceDynamicIndexStatsObject(
            inProcessCount: $stats->inProcessCount,
            errorsCount: $stats->errorsCount,
            totalCount: $stats->totalCount,
        );
    }
}
