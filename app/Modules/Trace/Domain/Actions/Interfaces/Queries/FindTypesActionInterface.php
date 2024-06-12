<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;

interface FindTypesActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     */
    public function handle(TraceFindTypesParameters $parameters): array;
}
