<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

/**
 * Which traces a watcher counts: by service, by type, by tag, by status.
 *
 * The status is matched on each write of a trace as it stands at that moment: the count
 * of a trace is taken with the status it started with, its duration with the final one.
 *
 * An empty list means "not filtered by this". Tags and statuses match on any of them.
 */
readonly class WatcherTraceFilterObject
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags
     * @param string[] $statuses
     */
    public function __construct(
        public array $serviceIds = [],
        public array $types = [],
        public array $tags = [],
        public array $statuses = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service_ids' => $this->serviceIds,
            'types'       => $this->types,
            'tags'        => $this->tags,
            'statuses'    => $this->statuses,
        ];
    }
}
