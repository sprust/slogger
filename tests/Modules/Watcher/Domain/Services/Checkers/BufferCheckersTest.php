<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\WatcherCountWindow;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class BufferCheckersTest extends TestCase
{
    use WatcherFactoryTrait;

    public function testAFullBufferIsReported(): void
    {
        $this->assertNotNull($this->checkBuffer(bufferCount: 1000, threshold: 1000));
    }

    public function testABufferUnderTheThresholdSaysNothing(): void
    {
        $this->assertNull($this->checkBuffer(bufferCount: 999, threshold: 1000));
    }

    /**
     * Mongo would not answer. Passing zero here would report an empty buffer to the one
     * watcher whose whole job is to notice a full one — the single worst answer available.
     */
    public function testABufferSizeThatCouldNotBeReadIsNotReportedAsEmpty(): void
    {
        $this->assertNull($this->checkBuffer(bufferCount: null, threshold: 1));
    }

    public function testNewInvalidDocumentsAreReported(): void
    {
        $this->assertNotNull($this->checkInvalid(countSince: 3, threshold: 1));
    }

    public function testNoNewInvalidDocumentsSayNothing(): void
    {
        $this->assertNull($this->checkInvalid(countSince: 0, threshold: 1));
    }

    /**
     * A watcher switched on today must not open an incident about documents that failed
     * last week, so the first check looks one cooldown back rather than at everything.
     */
    public function testTheFirstCheckLooksOneCooldownBack(): void
    {
        $now = Carbon::parse('2026-09-07 12:00:00');

        $action = $this->createMock(CountInvalidTraceBufferSinceAction::class);
        $action->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn(Carbon $since): bool => $since->toDateTimeString() === '2026-09-07 11:50:00'
            ))
            ->willReturn(0);

        new InvalidBufferGrownChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(),
                cooldownSeconds: 600
            ),
            new WatcherCheckContextObject($now, null)
        );
    }

    /**
     * Afterwards the window starts where the watcher last spoke — not where it last
     * looked.
     *
     * The check runs every minute and the cooldown throws most of those triggers away.
     * Moving the window on every look meant the count reset while nobody was being told:
     * with a ten-minute cooldown and three failures a minute, the event that finally got
     * through said three, and the thirty that failed in between were reported by nothing.
     */
    public function testTheWindowStartsWhereTheWatcherLastSpoke(): void
    {
        $this->assertWindowStartsAt(
            '2026-09-07 11:50:00',
            lastTriggeredAt: Carbon::parse('2026-09-07 11:50:00'),
            // Looked at a minute ago and said nothing: the ten minutes before that are
            // still unreported.
            lastCheckedAt: Carbon::parse('2026-09-07 11:59:00')
        );
    }

    /**
     * Never before the watcher started collecting. One switched off for a week and back on
     * this morning would otherwise open an incident about what failed while it was off.
     */
    public function testTheWindowNeverReachesBackBeforeCollectingStarted(): void
    {
        $this->assertWindowStartsAt(
            '2026-09-07 11:30:00',
            lastTriggeredAt: Carbon::parse('2026-09-01 09:00:00'),
            collectSince: Carbon::parse('2026-09-07 11:30:00')
        );
    }

    private function assertWindowStartsAt(
        string $expected,
        ?Carbon $lastTriggeredAt = null,
        ?Carbon $lastCheckedAt = null,
        ?Carbon $collectSince = null
    ): void {
        $action = $this->createMock(CountInvalidTraceBufferSinceAction::class);
        $action->expects($this->once())
            ->method('handle')
            ->with($this->callback(
                static fn(Carbon $since): bool => $since->toDateTimeString() === $expected
            ))
            ->willReturn(0);

        new InvalidBufferGrownChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(),
                collectSince: $collectSince,
                lastTriggeredAt: $lastTriggeredAt,
                lastCheckedAt: $lastCheckedAt,
                cooldownSeconds: 600
            ),
            new WatcherCheckContextObject(Carbon::parse('2026-09-07 12:00:00'), null)
        );
    }

    private function checkBuffer(?int $bufferCount, int $threshold): mixed
    {
        return new BufferOverflowChecker()->check(
            $this->watcher(
                WatcherTypeEnum::BufferOverflow,
                new BufferOverflowSettingsObject(threshold: $threshold)
            ),
            new WatcherCheckContextObject(Carbon::now(), $bufferCount)
        );
    }

    private function checkInvalid(int $countSince, int $threshold): mixed
    {
        $action = $this->createMock(CountInvalidTraceBufferSinceAction::class);
        $action->method('handle')->willReturn($countSince);

        return new InvalidBufferGrownChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(threshold: $threshold),
                lastTriggeredAt: Carbon::now()->subMinute()
            ),
            new WatcherCheckContextObject(Carbon::now(), null)
        );
    }
}
