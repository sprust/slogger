<?php

namespace Tests\Modules\Watcher\Entities;

use App\Modules\Watcher\Domain\Actions\Mutations\TrimWatcherTimelineAction;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * How much of a line a watcher may ask about, and how the answer knows when it cannot.
 *
 * Nothing in the running system reports a window that reaches past its line. The buckets
 * that are not there are simply not counted, and every checker reads what is missing as an
 * absence — a spike watcher divides the part it found by the whole baseline it asked for
 * and calls steady traffic a rise, hour after hour.
 */
class WatcherTimelineDepthTest extends TestCase
{
    /**
     * The trimming has to keep everything the deepest window can reach for, including the
     * lag by which that window is already shifted back.
     */
    public function testTheTrimmingKeepsMoreThanTheDeepestWindowAsksFor(): void
    {
        $slack = new ReflectionClass(TrimWatcherTimelineAction::class)
            ->getConstant('SLACK_MINUTES');

        $this->assertGreaterThanOrEqual(
            WatcherTimelineAnalyzer::LAG_SECONDS,
            $slack * 60,
            'the trimming cuts inside the window the lag pushes the checkers back to'
        );
    }

    /**
     * At its best — every element a moment of its own — the cap holds what the deepest
     * settings plus that slack need. It is only ever at its best, though: the receiver
     * writes an element per flush, not per moment, so a busy watcher spends several on one
     * timestamp. That is what startsAfter() is for.
     */
    public function testTheDeepestSettingsFitTheCapWhenNoMomentIsWrittenTwice(): void
    {
        $slack = new ReflectionClass(TrimWatcherTimelineAction::class)
            ->getConstant('SLACK_MINUTES');

        $keptSeconds = (WatcherTimelineObject::MAX_DEPTH_MINUTES + $slack) * 60;

        $this->assertLessThanOrEqual(
            WatcherTimelineObject::RECEIVER_ELEMENT_CAP,
            $keptSeconds / 15,
            'the deepest settings need more 15-second buckets than the receiver keeps'
        );
    }

    /** Below the cap nothing was dropped, so a quiet head is an answer, not a gap. */
    public function testALineUnderTheCapIsTakenAtItsWord(): void
    {
        $timeline = $this->timeline(startsAt: '2026-09-08 11:55:00', truncated: false);

        $this->assertFalse($timeline->startsAfter(Carbon::parse('2026-09-08 11:00:00')));
    }

    /** At the cap the head was cut, and the first bucket is where the line really begins. */
    public function testALineAtTheCapAdmitsWhereItBegins(): void
    {
        $timeline = $this->timeline(startsAt: '2026-09-08 11:55:00', truncated: true);

        $this->assertTrue($timeline->startsAfter(Carbon::parse('2026-09-08 11:00:00')));
        $this->assertFalse($timeline->startsAfter(Carbon::parse('2026-09-08 11:56:00')));
    }

    private function timeline(string $startsAt, bool $truncated): WatcherTimelineObject
    {
        return new WatcherTimelineObject(
            watcherId: 1,
            buckets: [
                new WatcherTimelineBucketObject(
                    at: Carbon::parse($startsAt),
                    count: 1,
                    durationCount: 0,
                    durationSum: 0,
                    durationMax: 0,
                    groups: []
                ),
            ],
            truncated: $truncated
        );
    }
}
