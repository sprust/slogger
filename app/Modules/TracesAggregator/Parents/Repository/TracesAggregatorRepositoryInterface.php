<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentsParameters;

interface TracesAggregatorRepositoryInterface
{
    public function findParents(TracesAggregatorParentsParameters $parameters): TracesAggregatorParentObjects;
}
