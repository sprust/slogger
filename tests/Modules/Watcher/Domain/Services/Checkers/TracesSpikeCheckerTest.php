<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactory;

class TracesSpikeCheckerTest extends TestCase
{
    use WatcherFactory;

    private const string NOW = '2026-09-07 12:00:00';

    /**
     * The two sides are different lengths, so they are compared as traces per minute.
     * Comparing totals would call every baseline longer than the window a spike, which is
     * to say: always.
     */
    public function testTheWindowIsComparedToTheBaselineRateNotItsTotal(): void
    {
        // 60 traces over 60 baseline minutes is 1/min; 10 over 5 window minutes is 2/min.
        // The totals say the baseline is six times larger; the rates say the window
        // doubled.
        $trigger = $this->check(
            baselinePerMinute: 1,
            windowTotal: 10,
            settings: new TracesSpikeSettingsObject(windowMinutes: 5, baselineMinutes: 60, growthPercent: 90)
        );

        $this->assertNotNull($trigger);
        $this->assertSame(100.0, $trigger->payload['growth_percent']);
    }

    public function testAGrowthBelowTheThresholdSaysNothing(): void
    {
        // 1.5/min against 1/min is 50%.
        $this->assertNull(
            $this->check(
                baselinePerMinute: 1,
                windowTotal: 7,
                settings: new TracesSpikeSettingsObject(windowMinutes: 5, baselineMinutes: 60, growthPercent: 90)
            )
        );
    }

    /**
     * An empty baseline makes every arrival an infinite rise. Firing on that would mean a
     * watcher shouting the moment a quiet service wakes up — an arrival, not a spike.
     */
    public function testNothingIsSaidWhenThereIsNothingToGrowFrom(): void
    {
        $this->assertNull($this->check(baselinePerMinute: 0, windowTotal: 500));
    }

    /**
     * A watcher created ten minutes ago has no hour to compare against. The window would
     * be measured against a baseline that is empty because nobody was counting, which is
     * the same false alarm as above with a slower fuse.
     */
    public function testNothingIsSaidUntilTheBaselineHasActuallyBeenCollected(): void
    {
        $this->assertNull(
            $this->check(
                baselinePerMinute: 1,
                windowTotal: 500,
                collectSince: Carbon::parse(self::NOW)->subMinutes(10)
            )
        );
    }

    public function testAWatcherThatIsNotCollectingSaysNothing(): void
    {
        $this->assertNull($this->check(baselinePerMinute: 1, windowTotal: 500, collecting: false));
    }

    /** The event carries what was measured beside what it was measured against. */
    public function testTheEventSaysWhatItSaw(): void
    {
        $trigger = $this->check(baselinePerMinute: 1, windowTotal: 20);

        $this->assertNotNull($trigger);
        $this->assertSame(20, $trigger->payload['window_count']);
        $this->assertSame(4.0, $trigger->payload['window_per_minute']);
        $this->assertSame(1.0, $trigger->payload['baseline_per_minute']);
        $this->assertSame(90, $trigger->payload['threshold_percent']);
    }

    private function check(
        float $baselinePerMinute,
        int $windowTotal,
        ?TracesSpikeSettingsObject $settings = null,
        ?Carbon $collectSince = null,
        bool $collecting = true,
    ): ?\App\Modules\Watcher\Entities\WatcherTriggerObject {
        $settings ??= new TracesSpikeSettingsObject();

        $now = Carbon::parse(self::NOW);

        $analyzer = new WatcherTimelineAnalyzer();

        $to           = $analyzer->windowEnd($now);
        $windowFrom   = $to->clone()->subMinutes($settings->windowMinutes);
        $baselineFrom = $windowFrom->clone()->subMinutes($settings->baselineMinutes);

        $buckets = [];

        // One bucket per minute is enough: the analyzer sums whatever falls in the window,
        // and the resolution of the line does not change the arithmetic under test.
        for ($minute = 0; $minute < $settings->baselineMinutes; $minute++) {
            $buckets[] = $this->bucket(
                $baselineFrom->clone()->addMinutes($minute),
                (int) $baselinePerMinute
            );
        }

        $buckets[] = $this->bucket($windowFrom->clone(), $windowTotal);

        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->method('find')->willReturn(new WatcherTimelineObject(1, $buckets));

        // Collecting since before the baseline unless the case is about the opposite.
        $since = $collecting
            ? $collectSince ?? $baselineFrom->clone()->subMinute()
            : null;

        return new TracesSpikeChecker($repository, $analyzer)->check(
            $this->spikeWatcher($settings, $since),
            new WatcherCheckContextObject($now, null)
        );
    }

    private function spikeWatcher(TracesSpikeSettingsObject $settings, ?Carbon $collectSince): WatcherObject
    {
        return $this->watcher(WatcherTypeEnum::TracesSpike, $settings, collectSince: $collectSince);
    }

    private function bucket(Carbon $at, int $count): WatcherTimelineBucketObject
    {
        return new WatcherTimelineBucketObject($at, $count, 0, 0, 0, []);
    }
}
