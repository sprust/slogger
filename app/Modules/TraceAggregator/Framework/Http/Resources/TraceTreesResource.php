<?php

namespace App\Modules\TraceAggregator\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreesResource extends AbstractApiResource
{
    private int $tracesCount;
    #[OaListItemTypeAttribute(TraceTreeResource::class, isRecursive: true)]
    private array $items;

    public function __construct(TraceTreeObjects $trees)
    {
        parent::__construct($trees);

        $this->tracesCount = $trees->tracesCount;
        $this->items       = TraceTreeResource::mapIntoMe($trees->items);
    }
}
