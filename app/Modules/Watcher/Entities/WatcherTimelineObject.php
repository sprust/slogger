<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * A watcher's line: its buckets, oldest first, each timestamp appearing once.
 */
readonly class WatcherTimelineObject
{
    /**
     * @param WatcherTimelineBucketObject[] $buckets
     */
    public function __construct(
        public int $watcherId,
        public array $buckets
    ) {
    }
}
