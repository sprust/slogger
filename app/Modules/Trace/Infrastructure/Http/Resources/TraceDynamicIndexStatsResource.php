<?php

namespace App\Modules\Trace\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDynamicIndexStatsResource extends AbstractApiResource
{
    private int $in_process_count;
    private int $errors_count;
    private int $total_count;
    #[OaListItemTypeAttribute(TraceDynamicIndexInProcessResource::class)]
    private array $indexes_in_process;

    public function __construct(TraceDynamicIndexStatsObject $resource)
    {
        parent::__construct($resource);

        $this->in_process_count   = $resource->inProcessCount;
        $this->errors_count       = $resource->errorsCount;
        $this->total_count        = $resource->totalCount;
        $this->indexes_in_process = TraceDynamicIndexInProcessResource::mapIntoMe(
            $resource->indexesInProcess
        );
    }
}
