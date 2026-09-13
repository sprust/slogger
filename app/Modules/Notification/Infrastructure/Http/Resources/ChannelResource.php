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
    private string $created_at;
    private string $updated_at;

    public function __construct(ChannelObject $resource)
    {
        parent::__construct($resource);

        $this->id         = $resource->id;
        $this->name       = $resource->name;
        $this->type       = $resource->type->value;
        $this->enabled    = $resource->enabled;
        $this->created_at = $resource->createdAt->toDateTimeString();
        $this->updated_at = $resource->updatedAt->toDateTimeString();
    }
}
