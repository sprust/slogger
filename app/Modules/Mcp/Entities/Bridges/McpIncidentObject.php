<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpIncidentObject
{
    public function __construct(public McpIncidentDataObject $incident, public ?McpIncidentWatcherObject $watcher)
    {
    }
}
