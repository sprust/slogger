<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceDynamicIndexResource extends AbstractApiResource
{
    private string $id;
    private string $name;
    #[OaListItemTypeAttribute(TraceDynamicIndexFieldResource::class)]
    private array $fields;
    private bool $inProcess;
    private bool $created;
    private ?string $error;
    private string $actualUntilAt;
    private string $createdAt;

    public function __construct(TraceDynamicIndexObject $resource)
    {
        parent::__construct($resource);

        $this->id            = $resource->id;
        $this->name          = $resource->name;
        $this->fields        = TraceDynamicIndexFieldResource::mapIntoMe($resource->fields);
        $this->inProcess     = $resource->inProcess;
        $this->created       = $resource->created;
        $this->error         = $resource->error;
        $this->actualUntilAt = $resource->actualUntilAt->toDateTimeString();
        $this->createdAt     = $resource->createdAt->toDateTimeString();
    }
}
