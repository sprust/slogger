<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Mcp\Entities\McpObject;

class McpResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private string $token;
    private bool $enabled;
    private int $requests_count;
    private ?string $last_used_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(McpObject $resource)
    {
        parent::__construct($resource);

        $this->id             = $resource->id;
        $this->name           = $resource->name;
        $this->token          = $resource->token;
        $this->enabled        = $resource->enabled;
        $this->requests_count = $resource->requestsCount;
        $this->last_used_at   = $resource->lastUsedAt?->toDateTimeString();
        $this->created_at     = $resource->createdAt->toDateTimeString();
        $this->updated_at     = $resource->updatedAt->toDateTimeString();
    }
}
