<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTypesParameters;

interface TraceContentRepositoryInterface
{
    /**
     * @return string[]
     */
    public function findTypes(TraceFindTypesParameters $parameters): array;

    /**
     * @return string[]
     */
    public function findTags(TraceFindTagsParameters $parameters): array;

    /**
     * @return string[]
     */
    public function findStatuses(TraceFindStatusesParameters $parameters): array;
}
