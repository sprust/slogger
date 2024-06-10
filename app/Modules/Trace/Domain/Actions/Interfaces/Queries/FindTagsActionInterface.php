<?php

namespace App\Modules\Trace\Domain\Actions\Interfaces\Queries;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTagsParameters;

interface FindTagsActionInterface
{
    /**
     * @return TraceStringFieldObject[]
     */
    public function handle(TraceFindTagsParameters $parameters): array;
}
