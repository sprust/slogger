<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTracesAction
{
    public function __construct(
        private TraceRepositoryInterface $repository
    ) {
    }

    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        return $this->repository->find($parameters);
    }
}
