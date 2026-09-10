<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\TrimWatcherTimelineAction;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

/**
 * How much of a line is worth keeping: what the watcher can still be asked about, and a
 * little more.
 */
class TrimWatcherTimelineActionTest extends TestCase
{
    use WatcherFactoryTrait;

    private const string NOW = '2026-09-07 12:00:00';

    /** The window plus the slack that covers the lag and a setting changed in between. */
    public function testTheCutFollowsTheWindow(): void
    {
        $this->assertCutAt(
            '2026-09-07 11:45:00',
            new NoNewTracesSettingsObject(periodMinutes: 10)
        );
    }

    /** A counting watcher reaches back as far as its window and no further. */
    public function testACountingWatcherKeepsItsWindow(): void
    {
        $this->assertCutAt(
            '2026-09-07 11:50:00',
            new ManyTracesSettingsObject(windowMinutes: 5, threshold: 1000)
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
                $settings instanceof ManyTracesSettingsObject
                    ? WatcherTypeEnum::ManyTraces
                    : WatcherTypeEnum::NoNewTraces,
                $settings
            ),
            Carbon::parse(self::NOW)
        );
    }
}
