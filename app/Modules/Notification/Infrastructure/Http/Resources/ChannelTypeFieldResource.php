<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\ChannelTypeFieldObject;

class ChannelTypeFieldResource extends AbstractApiResource
{
    private string $key;
    private string $title;
    private string $description;
    private bool $secret;
    private int $max_length;

    public function __construct(ChannelTypeFieldObject $resource)
    {
        parent::__construct($resource);

        $this->key         = $resource->key;
        $this->title       = $resource->title;
        $this->description = $resource->description;
        $this->secret      = $resource->secret;
        $this->max_length  = $resource->maxLength;
    }
}
