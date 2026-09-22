<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeFilteredObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeFilteredResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceTreeResource::class)]
    private array $items;
    private int $matched_count;
    private bool $truncated;

    public function __construct(TraceTreeFilteredObject $resource)
    {
        parent::__construct($resource);

        $this->items = array_map(
            static fn(TraceTreeRawObject $item): TraceTreeResource => new TraceTreeResource($item),
            $resource->items
        );

        $this->matched_count = $resource->matchedCount;
        $this->truncated     = $resource->truncated;
    }
}
