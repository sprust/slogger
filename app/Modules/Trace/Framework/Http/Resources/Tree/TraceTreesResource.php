<?php

namespace App\Modules\Trace\Framework\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\Tree\TraceTreeObjects;
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
