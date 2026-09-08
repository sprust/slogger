<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\ChannelObject;

class ChannelResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private string $type;
    private bool $enabled;
    private bool $on_opened;
    private bool $on_event;
    private bool $on_closed;
    private string $created_at;
    private string $updated_at;

    public function __construct(ChannelObject $resource)
    {
        parent::__construct($resource);

        $this->id         = $resource->id;
        $this->name       = $resource->name;
        $this->type       = $resource->type->value;
        $this->enabled    = $resource->enabled;
        $this->on_opened  = $resource->onOpened;
        $this->on_event   = $resource->onEvent;
        $this->on_closed  = $resource->onClosed;
        $this->created_at = $resource->createdAt->toDateTimeString();
        $this->updated_at = $resource->updatedAt->toDateTimeString();
    }
}
