<?php

declare(strict_types=1);

namespace App\Modules\Tools\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Tools\Infrastructure\Services\Objects\ToolLinksObject;

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
