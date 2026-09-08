<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\TrimWatcherTimelineAction;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactory;

/**
 * How much of a line is worth keeping: what the watcher can still be asked about, and a
 * little more.
 */
class TrimWatcherTimelineActionTest extends TestCase
{
    use WatcherFactory;

    private const string NOW = '2026-09-07 12:00:00';

    /** The window plus the slack that covers the lag and a setting changed in between. */
    public function testTheCutFollowsTheWindow(): void
    {
        $this->assertCutAt(
            '2026-09-07 11:45:00',
            new NoNewTracesSettingsObject(periodMinutes: 10)
        );
    }

    /**
     * A spike watcher reads its window and the baseline before it, so the depth is both of
     * them — cutting to the window alone would take away what it compares against.
     */
    public function testASpikeKeepsItsBaselineToo(): void
    {
        $this->assertCutAt(
            '2026-09-07 10:50:00',
            new TracesSpikeSettingsObject(windowMinutes: 5, baselineMinutes: 60)
        );
    }

    /**
     * A watcher that reads counters has no line at all — the receiver never writes one for
     * it — so there is nothing here to cut and no query to make.
     */
    public function testAWatcherWithoutALineIsLeftAlone(): void
    {
        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->expects($this->never())->method('trim');

        new TrimWatcherTimelineAction($repository)->handle(
            $this->watcher(WatcherTypeEnum::BufferOverflow, new BufferOverflowSettingsObject()),
            Carbon::parse(self::NOW)
        );
    }

    private function assertCutAt(string $expected, object $settings): void
    {
        $repository = $this->createMock(WatcherTimelineRepository::class);
        $repository->expects($this->once())
            ->method('trim')
            ->with(
                1,
                $this->callback(
                    static fn(Carbon $before): bool => $before->toDateTimeString() === $expected
                )
            );

        new TrimWatcherTimelineAction($repository)->handle(
            $this->watcher(
                $settings instanceof TracesSpikeSettingsObject
                    ? WatcherTypeEnum::TracesSpike
                    : WatcherTypeEnum::NoNewTraces,
                $settings
            ),
            Carbon::parse(self::NOW)
        );
    }
}
