<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\ChannelTypeObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class ChannelTypeResource extends AbstractApiResource
{
    private string $type;
    private string $title;
    private string $description;
    /** @var ChannelTypeFieldResource[] */
    #[OaListItemTypeAttribute(ChannelTypeFieldResource::class)]
    private array $fields;

    public function __construct(ChannelTypeObject $resource)
    {
        parent::__construct($resource);

        $this->type        = $resource->type->value;
        $this->title       = $resource->title;
        $this->description = $resource->description;
        $this->fields      = ChannelTypeFieldResource::mapIntoMe($resource->fields);
    }
}
