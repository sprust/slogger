<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Entities\Bridges\McpServiceObject;
use App\Modules\Service\Entities\ServiceObject;

readonly class McpServiceObjectTransport
{
    public function fromService(ServiceObject $serviceObject): McpServiceObject
    {
        return new McpServiceObject(
            id: $serviceObject->id,
            name: $serviceObject->name,
        );
    }
}
