<?php

namespace App\Modules\Tools\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Tools\Framework\Services\Objects\ToolLinksObject;

class ToolLinksResource extends AbstractApiResource
{
    private string $name;
    private string $url;

    public function __construct(ToolLinksObject $resource)
    {
        parent::__construct($resource);

        $this->name = $resource->name;
        $this->url  = $resource->url;
    }
}
