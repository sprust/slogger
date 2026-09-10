<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/**
 * What an event of this watcher carries.
 */
readonly class InvalidBufferGrownEventPayloadObject implements WatcherEventPayloadInterface
{
    public function __construct(
        public InvalidBufferGrownEventSettingsObject $settings,
        public InvalidBufferGrownEventMeasuredObject $measured
    ) {
    }
}
