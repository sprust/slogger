<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\WatcherTimelineAnalyzer;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherTimelineBucketObject;
use App\Modules\Watcher\Entities\WatcherTimelineObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

/**
 * The only watcher that reports an absence, and so the only one that can mistake "nobody
 * was counting" for "nothing happened".
 */
class NoNewTracesCheckerTest extends TestCase
{
    use WatcherFactoryTrait;

    private const string NOW = '2026-09-07 12:00:00';

    public function testSilenceThroughTheWholeWindowIsReported(): void
    {
        $trigger = $this->check(bucketsAtMinutesAgo: []);

        $this->assertNotNull($trigger);
        $this->assertSame(10, $trigger->settings['period_minutes']);
    }

    public function testOneTraceInTheWindowIsEnoughToStayQuiet(): void
    {
        $this->assertNull($this->check(bucketsAtMinutesAgo: [5]));
    }

    /**
     * The last minute of the line is still being written: the receiver holds a bucket
     * until it has been closed for fifteen seconds. A trace that arrived just now must not
     * count, or the window would never be judged on settled data.
     */
    public function testATraceInsideTheLagDoesNotCount(): void
    {
        $this->assertNotNull($this->check(bucketsAtMinutesAgo: [0]));
    }

    /** A trace older than the window is not in the window. */
    public function testATraceBeforeTheWindowDoesNotCount(): void
    {
        $this->assertNotNull($this->check(bucketsAtMinutesAgo: [30]));
    }

    /**
     * The window reaches back further than the watcher has been collecting, so it is
     * empty because nobody was counting. Reporting that would make every new watcher fire
     * once, the moment it was created.
     */
    public function testAWindowOlderThanTheCollectionIsNotSilence(): void
    {
        $this->assertNull(
            $this->check(bucketsAtMinutesAgo: [], collectSince: Carbon::parse(self::NOW)->subMinutes(3))
        );
    }

    public function testAWatcherThatIsNotCollectingSaysNothing(): void
    {
        $this->assertNull($this->check(bucketsAtMinutesAgo: [], collecting: false));
    }

    /**
     * A line at the receiver's cap had its head cut, so an empty window may be a stretch
     * that was dropped rather than one where nothing happened. Reporting an absence from
     * it would be a false alarm — the one thing this watcher must not produce.
     */
    public function testATruncatedLineThatStartsInsideTheWindowSaysNothing(): void
    {
        $this->assertNull($this->check(bucketsAtMinutesAgo: [2], truncated: true));
    }

    /** Below the cap nothing was dropped, so the same line is an answer. */
    public function testAnUntruncatedLineStartingInsideTheWindowStillReports(): void
    {
        $this->assertNotNull($this->check(bucketsAtMinutesAgo: [30], truncated: false));
    }

    private function check(
        array $bucketsAtMinutesAgo,
        ?Carbon $collectSince = null,
        bool $collecting = true,
        bool $truncated = false
    ): ?WatcherTriggerObject {
        $now = Carbon::parse(self::NOW);

        $buckets = array_map(
            static fn(int $minutesAgo): WatcherTimelineBucketObject => new WatcherTimelineBucketObject(
                at: $now->clone()->subMinutes($minutesAgo),
                count: 1,
                durationCount: 0,
                durationSum: 0,
                durationMax: 0,
                groups: []
            ),
            $bucketsAtMinutesAgo
        );

        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->method('find')->willReturn(new WatcherTimelineObject(1, $buckets, $truncated));

        $watcher = $this->watcher(
            WatcherTypeEnum::NoNewTraces,
            new NoNewTracesSettingsObject(periodMinutes: 10),
            collectSince: $collecting ? $collectSince ?? $now->clone()->subHour() : null
        );

        return new NoNewTracesChecker($repository, new WatcherTimelineAnalyzer())->check(
            $watcher,
            new WatcherCheckContextObject($now, null)
        );
    }
}
