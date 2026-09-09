<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use Illuminate\Support\Carbon;

readonly class WatcherIncidentObject
{
    public function __construct(
        public string $id,
        public int $watcherId,
        public WatcherIncidentStatusEnum $status,
        public Carbon $firstEventAt,
        public Carbon $lastEventAt,
        public int $eventsCount,
        public ?Carbon $closedAt,
        public ?int $closedByUserId
    ) {
    }
}
