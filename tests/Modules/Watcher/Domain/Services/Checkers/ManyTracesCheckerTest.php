<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\Checkers\ManyTracesChecker;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class ManyTracesCheckerTest extends TestCase
{
    use WatcherFactoryTrait;

    private const string NOW = '2026-09-07 12:00:00';

    public function testTheLimitIsInclusive(): void
    {
        $this->assertNotNull($this->check(windowCounts: [400, 600], threshold: 1000));
    }

    public function testOneShortOfTheLimitSaysNothing(): void
    {
        $this->assertNull($this->check(windowCounts: [400, 599], threshold: 1000));
    }

    /** Every bucket in the window counts, not the busiest one. */
    public function testTheWholeWindowIsAddedUp(): void
    {
        $this->assertNotNull($this->check(windowCounts: [100, 100, 100], threshold: 300));
    }

    /** Traffic before the window is somebody else's business. */
    public function testWhatCameBeforeTheWindowDoesNotCount(): void
    {
        $this->assertNull(
            $this->check(windowCounts: [100], beforeWindowCount: 5000, threshold: 1000)
        );
    }

    /**
     * No warm-up guard, unlike the watchers that report an absence. A window nobody
     * collected for holds fewer traces than really arrived, never more, so a young
     * watcher can only stay quiet — and the count that does come back is real.
     */
    public function testAYoungWatcherStillCountsWhatItHasSeen(): void
    {
        $this->assertNotNull(
            $this->check(
                windowCounts: [1000],
                threshold: 1000,
                collectSince: Carbon::parse(self::NOW)->subSeconds(30)
            )
        );
    }

    public function testTheEventSaysWhatItSaw(): void
    {
        $trigger = $this->check(windowCounts: [700, 500], threshold: 1000);

        $this->assertNotNull($trigger);
        $this->assertSame(1200, $trigger->measured->windowCount);
        $this->assertSame(1000, $trigger->settings->threshold);
        $this->assertSame(5, $trigger->settings->windowMinutes);
    }

    /** The shapes behind the count, so the event says which traffic it was. */
    public function testTheBusiestShapesAreCarriedIntoTheEvent(): void
    {
        $trigger = $this->check(
            windowCounts: [1000],
            threshold: 1000,
            groups: [
                new WatcherTimelineGroupObject(1, 'http', ['api'], 400, 0, 0, 0, null),
                new WatcherTimelineGroupObject(2, 'job', [], 600, 0, 0, 0, null),
            ]
        );

        $this->assertNotNull($trigger);
        $this->assertCount(2, $trigger->groups);
        // Busiest first, and nothing timed: this watcher counts.
        $this->assertSame('job', $trigger->groups[0]->type);
        $this->assertSame(600, $trigger->groups[0]->count);
        $this->assertNull($trigger->groups[0]->durationMax);
        $this->assertNull($trigger->groups[0]->slowestTraceId);
    }

    /**
     * @param int[]                        $windowCounts one bucket each, inside the window
     * @param WatcherTimelineGroupObject[] $groups
     */
    private function check(
        array $windowCounts,
        int $threshold = 1000,
        int $beforeWindowCount = 0,
        array $groups = [],
        ?Carbon $collectSince = null,
    ): ?ManyTracesEventPayloadObject {
        $settings = new ManyTracesSettingsObject(windowMinutes: 5, threshold: $threshold);

        $now = Carbon::parse(self::NOW);

        $analyzer = new WatcherTimelineAnalyzer();

        $to   = $analyzer->windowEnd($now);
        $from = $to->clone()->subMinutes($settings->windowMinutes);

        $buckets = [];

        if ($beforeWindowCount > 0) {
            $buckets[] = $this->bucket($from->clone()->subMinute(), $beforeWindowCount);
        }

        foreach (array_values($windowCounts) as $index => $count) {
            $buckets[] = $this->bucket(
                $from->clone()->addMinutes($index),
                $count,
                $index === 0 ? $groups : []
            );
        }

        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->method('find')->willReturn(new WatcherTimelineObject(1, $buckets));

        return new ManyTracesChecker($repository, $analyzer)->check(
            $this->manyTracesWatcher($settings, $collectSince ?? $from->clone()->subMinute()),
            new WatcherCheckContextObject($now, null)
        );
    }

    private function manyTracesWatcher(ManyTracesSettingsObject $settings, ?Carbon $collectSince): WatcherObject
    {
        return $this->watcher(WatcherTypeEnum::ManyTraces, $settings, collectSince: $collectSince);
    }

    /**
     * @param WatcherTimelineGroupObject[] $groups
     */
    private function bucket(Carbon $at, int $count, array $groups = []): WatcherTimelineBucketObject
    {
        return new WatcherTimelineBucketObject($at, $count, 0, 0, 0, $groups);
    }
}
