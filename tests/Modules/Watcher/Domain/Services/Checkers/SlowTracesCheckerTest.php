<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineGroupObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class SlowTracesCheckerTest extends TestCase
{
    use WatcherFactoryTrait;

    private const string NOW = '2026-09-07 12:00:00';

    public function testATraceOverTheThresholdIsReported(): void
    {
        $trigger = $this->check(durations: [12.5]);

        $this->assertNotNull($trigger);
        $this->assertSame(12.5, $trigger->measured['slowest']);
    }

    public function testTheThresholdIsInclusive(): void
    {
        $this->assertNotNull($this->check(durations: [10.0]));
    }

    public function testTracesUnderTheThresholdSayNothing(): void
    {
        $this->assertNull($this->check(durations: [9.999, 1.0]));
    }

    public function testNothingSlowMeansNothingToSay(): void
    {
        $this->assertNull($this->check(durations: []));
    }

    /**
     * The id is the reason this watcher is worth having: without it the event says a
     * trace was slow and gives nobody anything to open.
     */
    public function testTheEventNamesTheSlowestTrace(): void
    {
        $trigger = $this->check(durations: [11.0, 30.0, 15.0]);

        $this->assertNotNull($trigger);
        $this->assertSame(30.0, $trigger->groups[0]['duration_max']);
        $this->assertSame('trace-30', $trigger->groups[0]['trace_id']);
    }

    /** Shapes come back slowest first, so the worst is the first thing read. */
    public function testTheShapesAreOrderedBySlowest(): void
    {
        $trigger = $this->check(durations: [11.0, 30.0, 15.0]);

        $this->assertNotNull($trigger);
        $this->assertSame(
            [30.0, 15.0, 11.0],
            array_column($trigger->groups, 'duration_max')
        );
    }

    /**
     * @param float[] $durations
     */
    private function check(array $durations): ?WatcherTriggerObject
    {
        $now = Carbon::parse(self::NOW);

        // A shape of its own per duration, so that ordering and the named trace can both
        // be seen.
        $groups = [];
        $index  = 0;

        foreach ($durations as $duration) {
            $groups[] = new WatcherTimelineGroupObject(
                serviceId: 1,
                type: 'type-' . $index++,
                tags: [],
                count: 1,
                durationCount: 1,
                durationSum: $duration,
                durationMax: $duration,
                slowestTraceId: 'trace-' . (int) $duration
            );
        }

        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->method('find')->willReturn(
            new WatcherTimelineObject(1, [
                new WatcherTimelineBucketObject(
                    at: $now->clone()->subMinutes(2),
                    count: count($groups),
                    durationCount: count($groups),
                    durationSum: array_sum($durations),
                    durationMax: $durations ? max($durations) : 0,
                    groups: $groups
                ),
            ])
        );

        return new SlowTracesChecker($repository, new WatcherTimelineAnalyzer())->check(
            $this->watcher(
                WatcherTypeEnum::SlowTraces,
                new SlowTracesSettingsObject(duration: 10, windowMinutes: 5),
                collectSince: $now->clone()->subHour()
            ),
            new WatcherCheckContextObject($now, null)
        );
    }
}
