<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeChildrenResource extends AbstractApiResource
{
    #[OaListItemTypeAttribute(TraceTreeChildResource::class)]
    private array $items;
    private bool $has_more;

    public function __construct(TraceTreeChildrenObjects $trees)
    {
        parent::__construct($trees);

        $this->items    = TraceTreeChildResource::mapIntoMe($trees->items);
        $this->has_more = $trees->hasMore;
    }
}
