<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Mcp\Entities\McpSettingsObject;

class McpSettingsResource extends AbstractApiResource
{
    private string $server_name;
    private string $endpoint_url;

    public function __construct(McpSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->server_name  = $resource->serverName;
        $this->endpoint_url = $resource->endpointUrl;
    }
}
