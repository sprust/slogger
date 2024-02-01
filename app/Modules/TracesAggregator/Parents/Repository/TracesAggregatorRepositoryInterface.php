<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentTypeObjects;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentsParameters;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentTypesParameters;

interface TracesAggregatorRepositoryInterface
{
    public function findParentTypes(
        TracesAggregatorParentTypesParameters $parameters
    ): TracesAggregatorParentTypeObjects;

    public function findParents(TracesAggregatorParentsParameters $parameters): TracesAggregatorParentObjects;
}
