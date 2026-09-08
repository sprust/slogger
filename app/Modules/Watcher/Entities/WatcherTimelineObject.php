<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use Illuminate\Support\Carbon;

/**
 * A watcher's line: its buckets, oldest first, each timestamp appearing once.
 */
readonly class WatcherTimelineObject
{
    /**
     * How far back a line can reach, in minutes.
     *
     * The receiver keeps 720 buckets of 15 seconds (`maxBuckets` in
     * `watcher_timeline_repository`), and that ceiling holds whatever the panel does — it
     * is the guard against a stopped trimming task growing the array until `$push` starts
     * refusing. A watcher configured to look further back than that would be reading a
     * window the line cannot fill, and would take the part that is missing for an absence.
     *
     * Raising it means raising `maxBuckets` in the receiver in the same change.
     */
    public const int MAX_DEPTH_MINUTES = 180;

    /**
     * @param WatcherTimelineBucketObject[] $buckets
     */
    public function __construct(
        public int $watcherId,
        public array $buckets
    ) {
    }

    /** The first moment this line can answer for, or null when it holds nothing. */
    public function startsAt(): ?Carbon
    {
        return $this->buckets[0]->at ?? null;
    }
}
