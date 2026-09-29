<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;

readonly class McpIncidentObject
{
    public function __construct(
        public WatcherIncidentObject $incident,
        public ?WatcherObject $watcher
    ) {
    }
}
