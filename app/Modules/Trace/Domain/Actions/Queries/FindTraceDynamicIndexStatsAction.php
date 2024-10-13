<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDynamicIndexStatsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;

readonly class FindTraceDynamicIndexStatsAction implements FindTraceDynamicIndexStatsActionInterface
{
    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
    }

    public function handle(): TraceDynamicIndexStatsObject
    {
        return $this->traceDynamicIndexRepository->findStats();
    }
}
