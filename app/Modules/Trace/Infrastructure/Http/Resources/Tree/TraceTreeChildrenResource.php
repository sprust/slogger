<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeChildrenResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceTreeChildResource::class)]
    private array $items;
    private ?string $next_cursor;

    public function __construct(TraceTreeChildrenObject $resource)
    {
        parent::__construct($resource);

        $items = [];

        foreach ($resource->items as $item) {
            $items[] = new TraceTreeChildResource(
                resource: $item,
                childrenCount: $resource->childrenCounts[$item->traceId] ?? 0,
            );
        }

        $this->items       = $items;
        $this->next_cursor = $resource->nextCursor;
    }
}
