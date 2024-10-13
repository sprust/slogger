<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;

class TraceDynamicIndexStatsResource extends AbstractApiResource
{
    private int $inProcessCount;
    private int $errorsCount;
    private int $totalCount;

    public function __construct(TraceDynamicIndexStatsObject $resource)
    {
        parent::__construct($resource);

        $this->inProcessCount = $resource->inProcessCount;
        $this->errorsCount    = $resource->errorsCount;
        $this->totalCount     = $resource->totalCount;
    }
}
