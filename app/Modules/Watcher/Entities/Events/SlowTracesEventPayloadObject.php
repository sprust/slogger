<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;

/**
 * What an event of this watcher carries.
 *
 * The shapes behind it are here because this watcher is about traces. The ones that
 * read counters have no groups field at all, rather than an empty one.
 */
readonly class SlowTracesEventPayloadObject implements HasEventGroupsInterface
{
    /**
     * @param WatcherIncidentEventGroupObject[] $groups
     */
    public function __construct(
        public SlowTracesEventSettingsObject $settings,
        public SlowTracesEventMeasuredObject $measured,
        public array $groups
    ) {
    }
}
