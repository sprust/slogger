<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;

interface FindStatusesActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     */
    public function handle(TraceFindStatusesParameters $parameters): array;
}
