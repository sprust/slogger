<?php

namespace Tests\Modules\Watcher\Repositories\Services;

use App\Modules\Watcher\Repositories\Services\WatcherTimelineReader;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use SConcur\Bson\UTCDateTime;

/**
 * The receiver writes buckets additively and never reads the document first, which is what
 * makes its writes safe across restarts and across receivers. What it leaves behind is
 * duplicates — of a bucket, and of a shape inside one — and putting those right is this
 * class's whole job.
 */
class WatcherTimelineReaderTest extends TestCase
{
    public function testBucketsOfTheSameMomentAreAddedTogether(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', count: 3, durationMax: 2),
            $this->bucket('2026-09-07 10:00:00', count: 2, durationMax: 9),
        ]);

        $this->assertCount(1, $timeline->buckets);
        $this->assertSame(5, $timeline->buckets[0]->count);
        $this->assertSame(9.0, $timeline->buckets[0]->durationMax);
    }

    /** The same shape written twice is one shape seen more often, not two shapes. */
    public function testGroupsOfTheSameShapeAreAddedTogether(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', count: 1, groups: [
                $this->group(count: 1, durationMax: 3, traceId: 'a'),
            ]),
            $this->bucket('2026-09-07 10:00:00', count: 1, groups: [
                $this->group(count: 1, durationMax: 11, traceId: 'b'),
            ]),
        ]);

        $groups = $timeline->buckets[0]->groups;

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]->count);
        $this->assertSame(11.0, $groups[0]->durationMax);
        // The slower of the two keeps its name: it is the one worth looking at.
        $this->assertSame('b', $groups[0]->slowestTraceId);
    }

    public function testTheSlowestTraceKeepsItsStartThroughAMerge(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', count: 1, groups: [
                $this->group(count: 1, durationMax: 3, traceId: 'a', traceLoggedAt: '2026-09-07 09:59:57'),
            ]),
            $this->bucket('2026-09-07 10:00:00', count: 1, groups: [
                $this->group(count: 1, durationMax: 3600, traceId: 'b', traceLoggedAt: '2026-09-07 09:00:00'),
            ]),
        ]);

        $group = $timeline->buckets[0]->groups[0];

        $this->assertSame('b', $group->slowestTraceId);
        $this->assertSame('2026-09-07 09:00:00', $group->slowestTraceLoggedAt?->toDateTimeString());
    }

    public function testAGroupWrittenBeforeTracesWereDatedHasNoStart(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', groups: [$this->group(durationMax: 5)]),
        ]);

        $this->assertNull($timeline->buckets[0]->groups[0]->slowestTraceLoggedAt);
    }

    /** Tags order is the receiver's, not ours, but it must not split a shape. */
    public function testTagOrderDoesNotSplitAShape(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', groups: [$this->group(tags: ['b', 'a'])]),
            $this->bucket('2026-09-07 10:00:00', groups: [$this->group(tags: ['a', 'b'])]),
        ]);

        $this->assertCount(1, $timeline->buckets[0]->groups);
    }

    public function testBucketsComeBackOldestFirst(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:30'),
            $this->bucket('2026-09-07 10:00:00'),
            $this->bucket('2026-09-07 10:00:15'),
        ]);

        $this->assertSame(
            ['10:00:00', '10:00:15', '10:00:30'],
            array_map(fn($bucket) => $bucket->at->format('H:i:s'), $timeline->buckets)
        );
    }

    /**
     * The receiver writes an empty trace id for a shape whose traces have not finished.
     * Read as a name, it would send the panel looking for a trace called "".
     */
    public function testAShapeWithNoFinishedTraceHasNoSlowestOne(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            $this->bucket('2026-09-07 10:00:00', groups: [$this->group(traceId: '')]),
        ]);

        $this->assertNull($timeline->buckets[0]->groups[0]->slowestTraceId);
    }

    /** A document written before a field existed, or by something else entirely. */
    public function testAMalformedBucketIsSkippedRatherThanFatal(): void
    {
        $timeline = new WatcherTimelineReader()->read(1, [
            'not a bucket',
            ['c' => 5],
            $this->bucket('2026-09-07 10:00:00', count: 1),
        ]);

        $this->assertCount(1, $timeline->buckets);
        $this->assertSame(1, $timeline->buckets[0]->count);
    }

    public function testAnEmptyLineReadsAsNoBuckets(): void
    {
        $this->assertSame([], new WatcherTimelineReader()->read(1, [])->buckets);
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<string, mixed>
     */
    private function bucket(
        string $at,
        int $count = 0,
        float $durationMax = 0,
        array $groups = []
    ): array {
        return [
            't'    => new UTCDateTime(Carbon::parse($at)),
            'c'    => $count,
            'dc'   => 0,
            'dSum' => 0.0,
            'dMax' => $durationMax,
            'g'    => $groups,
        ];
    }

    /**
     * @param string[] $tags
     *
     * @return array<string, mixed>
     */
    private function group(
        int $count = 1,
        float $durationMax = 0,
        string $traceId = 'trace',
        array $tags = [],
        ?string $traceLoggedAt = null
    ): array {
        $group = [
            'sid'  => 1,
            'tp'   => 'http',
            'tgs'  => $tags,
            'c'    => $count,
            'dc'   => $durationMax > 0 ? 1 : 0,
            'dSum' => $durationMax,
            'dMax' => $durationMax,
            'tid'  => $traceId,
        ];

        if (!is_null($traceLoggedAt)) {
            $group['tlat'] = new UTCDateTime(Carbon::parse($traceLoggedAt));
        }

        return $group;
    }
}
