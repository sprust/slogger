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
     * How many elements the receiver keeps in the stored array (`maxBuckets` in
     * `watcher_timeline_repository`).
     *
     * Elements, not moments. The receiver's writes are additive and never read the
     * document first — that is what makes them safe across restarts and across receivers —
     * so a bucket reopened by a late trace arrives as a second element with the same
     * timestamp. A line of 720 elements can therefore be three hours of a service whose
     * traces all finish inside one bucket, or forty minutes of one whose traces run for
     * minutes. `$truncated` is what says which.
     */
    public const int RECEIVER_ELEMENT_CAP = 720;

    /**
     * How far back a watcher may be configured to look, in minutes.
     *
     * Short of the three hours the cap holds at its best, with room for the lag by which
     * every window is already shifted back and for the slack the trimming keeps. It is not
     * a guarantee — see the cap above — which is why the checkers that would misread a
     * short line also test `$truncated`.
     */
    public const int MAX_DEPTH_MINUTES = 175;

    /**
     * @param WatcherTimelineBucketObject[] $buckets
     * @param bool                          $truncated whether the stored array was at the
     *                                                 receiver's cap, so its head may have
     *                                                 been dropped
     */
    public function __construct(
        public int $watcherId,
        public array $buckets,
        public bool $truncated = false
    ) {
    }

    /** The first moment this line holds, or null when it holds nothing. */
    public function startsAt(): ?Carbon
    {
        return $this->buckets[0]->at ?? null;
    }

    /**
     * Whether the line is known not to reach back to $from.
     *
     * Only a line at the cap can answer this. Below the cap nothing was dropped, so a head
     * with no buckets in it is a stretch where nothing matched — which is an answer, not a
     * gap. At the cap the head was cut, and then the first bucket really is where the line
     * begins.
     */
    public function startsAfter(Carbon $from): bool
    {
        return $this->truncated && $this->startsAt()?->gt($from) === true;
    }
}
