<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherTypeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class WatcherTypeResource extends AbstractApiResource
{
    private string $type;
    private string $title;
    private string $description;
    private int $default_cooldown_seconds;
    private bool $has_trace_filter;
    /** @var WatcherTypeFieldResource[] */
    #[OaListItemTypeAttribute(WatcherTypeFieldResource::class)]
    private array $fields;

    public function __construct(WatcherTypeObject $resource)
    {
        parent::__construct($resource);

        $this->type                     = $resource->type->value;
        $this->title                    = $resource->title;
        $this->description              = $resource->description;
        $this->default_cooldown_seconds = $resource->defaultCooldownSeconds;
        $this->has_trace_filter         = $resource->hasTraceFilter;
        $this->fields                   = WatcherTypeFieldResource::mapIntoMe($resource->fields);
    }
}
