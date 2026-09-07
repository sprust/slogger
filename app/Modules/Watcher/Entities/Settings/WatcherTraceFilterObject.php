<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

/**
 * Which traces a watcher counts: by service, by type, by tag.
 *
 * Three dimensions and no more, and status deliberately not among them. A trace is
 * counted once, when it is first written, and at that moment its status is still the one
 * it started with — the final one arrives with the update. A status filter would
 * therefore count something other than what it promises.
 *
 * An empty list means "not filtered by this". Tags match on any of them.
 */
readonly class WatcherTraceFilterObject
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags
     */
    public function __construct(
        public array $serviceIds = [],
        public array $types = [],
        public array $tags = []
    ) {
    }
}
