<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Events;

use App\Modules\Watcher\Entities\WatcherIncidentObject;

/**
 * An incident was opened, added to, or closed.
 *
 * Carries the incident as it now stands rather than what changed: whoever listens is
 * showing a state, and the object is already assembled where this is raised.
 */
readonly class WatcherIncidentChangedEvent
{
    public function __construct(
        public WatcherIncidentObject $incident
    ) {
    }
}
