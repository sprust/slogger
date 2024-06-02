<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;

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
