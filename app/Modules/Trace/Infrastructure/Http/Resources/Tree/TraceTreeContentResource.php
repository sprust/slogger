<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeContentObjects;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTreeContentResource extends AbstractApiResource
{
    private int $count;
    #[OaListItemTypeAttribute(TraceTreeServiceResource::class)]
    private array $services;
    #[OaListItemTypeAttribute(TraceTreeStringableResource::class)]
    private array $types;
    #[OaListItemTypeAttribute(TraceTreeStringableResource::class)]
    private array $tags;
    #[OaListItemTypeAttribute(TraceTreeStringableResource::class)]
    private array $statuses;

    public function __construct(TraceTreeContentObjects $resource)
    {
        parent::__construct($resource);

        $this->count    = $resource->count;
        $this->services = TraceTreeServiceResource::mapIntoMe($resource->services);
        $this->types    = TraceTreeStringableResource::mapIntoMe($resource->types);
        $this->tags     = TraceTreeStringableResource::mapIntoMe($resource->tags);
        $this->statuses = TraceTreeStringableResource::mapIntoMe($resource->statuses);
    }
}
