<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * How much there is to deal with, in one number.
 *
 * What the header's badge stands on, so that a problem noticed while the panel is on
 * another page is still visible without opening the watchers list.
 */
readonly class WatcherIncidentStatObject
{
    public function __construct(
        public int $openedCount
    ) {
    }
}
