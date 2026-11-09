<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Entities\Bridges\McpIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;

readonly class McpIncidentEventObjectTransport
{
    /**
     * @param array<string, mixed>|null $numbers
     */
    public function fromWatcher(WatcherIncidentEventObject $watcherIncidentEventObject, ?array $numbers): McpIncidentEventObject
    {
        return new McpIncidentEventObject(
            occurredAt: $watcherIncidentEventObject->occurredAt,
            numbers: $numbers,
        );
    }
}
