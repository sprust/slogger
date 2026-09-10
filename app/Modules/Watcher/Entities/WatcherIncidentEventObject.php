<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use Illuminate\Support\Carbon;

readonly class WatcherIncidentEventObject
{
    /**
     * @param WatcherEventPayloadInterface|null $payload null for an event stored under a
     *                                                   shape this build cannot read
     */
    public function __construct(
        public string $id,
        public string $incidentId,
        public ?WatcherEventPayloadInterface $payload,
        public Carbon $occurredAt
    ) {
    }
}
