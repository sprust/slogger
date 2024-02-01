<?php

namespace App\Modules\TracesAggregator\Repository;

use App\Modules\TracesAggregator\Dto\Objects\Parents\TracesAggregatorParentObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TracesAggregatorParentsParameters;

interface TracesAggregatorRepositoryInterface
{
    public function findParents(TracesAggregatorParentsParameters $parameters): TracesAggregatorParentObjects;
}
