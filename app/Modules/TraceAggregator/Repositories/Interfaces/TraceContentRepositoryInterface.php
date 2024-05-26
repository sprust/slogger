<?php

namespace App\Modules\TraceAggregator\Repositories\Interfaces;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceStringFieldDto;

interface TraceContentRepositoryInterface
{
    /**
     * @return TraceStringFieldDto[]
     */
    public function findTypes(TraceFindTypesParameters $parameters): array;

    /**
     * @return TraceStringFieldDto[]
     */
    public function findTags(TraceFindTagsParameters $parameters): array;

    /**
     * @return TraceStringFieldDto[]
     */
    public function findStatuses(TraceFindStatusesParameters $parameters): array;
}
