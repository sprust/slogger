<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexStatsObject;

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
