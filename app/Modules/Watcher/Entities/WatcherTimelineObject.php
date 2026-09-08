<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

/**
 * A watcher's line: its buckets, oldest first, each timestamp appearing once.
 */
readonly class WatcherTimelineObject
{
    /**
     * The least a line is guaranteed to reach back, in minutes.
     *
     * The receiver keeps 720 buckets of 15 seconds (`maxBuckets` in
     * `watcher_timeline_repository`), and that ceiling holds whatever the panel does — it
     * is the guard against a stopped trimming task growing the array until `$push` starts
     * refusing. Traffic thin enough to leave gaps stretches those buckets over more time,
     * never less, so this is a floor.
     */
    public const int RECEIVER_CEILING_MINUTES = 180;

    /**
     * How far back a watcher may be configured to look, in minutes.
     *
     * Deliberately short of the ceiling above. A window reaching past what the line holds
     * reads buckets nobody kept, and every checker takes what is missing for an absence —
     * a spike watcher divides the part it found by the whole baseline it asked for, and
     * calls steady traffic a rise, for ever. The margin covers
     * WatcherTimelineAnalyzer::LAG_SECONDS, by which every window is already shifted back.
     *
     * Raising it means raising `maxBuckets` in the receiver in the same change.
     */
    public const int MAX_DEPTH_MINUTES = 175;

    /**
     * @param WatcherTimelineBucketObject[] $buckets
     */
    public function __construct(
        public int $watcherId,
        public array $buckets
    ) {
    }
}
