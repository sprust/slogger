<?php

namespace Tests\Modules\Watcher\Entities;

use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use PHPUnit\Framework\TestCase;

/**
 * What a watcher may ask for has to be less than what the line is guaranteed to hold.
 *
 * Nothing in the running system reports the difference. A window reaching past the line
 * simply reads buckets nobody kept, and the checkers take what is missing for an absence —
 * a spike watcher divides the part it found by the whole baseline it asked for and calls
 * steady traffic a rise, hour after hour. So the two numbers are pinned here, and the
 * receiver's `maxBuckets` has to move with them.
 */
class WatcherTimelineDepthTest extends TestCase
{
    /** 720 buckets of 15 seconds, every one of them filled. */
    public function testTheCeilingIsWhatTheReceiverKeeps(): void
    {
        $this->assertSame(720 * 15 / 60, WatcherTimelineObject::RECEIVER_CEILING_MINUTES);
    }

    /**
     * With room for the lag on top, by which every window is already shifted back: a
     * window of exactly the ceiling would ask for a minute that had fallen off the end.
     */
    public function testTheDeepestWindowFitsUnderTheCeilingWithTheLagOnTop(): void
    {
        $lagMinutes = (int) ceil(WatcherTimelineAnalyzer::LAG_SECONDS / 60);

        $this->assertLessThan(
            WatcherTimelineObject::RECEIVER_CEILING_MINUTES,
            WatcherTimelineObject::MAX_DEPTH_MINUTES + $lagMinutes
        );
    }
}
