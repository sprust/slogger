<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactory;

class BufferCheckersTest extends TestCase
{
    use WatcherFactory;

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

        new InvalidBufferGrownChecker($action)->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(),
                cooldownSeconds: 600
            ),
            new WatcherCheckContextObject($now, null)
        );
    }

    /** Afterwards the window starts where the last check ended: nothing in between is missed. */
    public function testLaterChecksLookFromTheLastCheck(): void
    {
        $now      = Carbon::parse('2026-09-07 12:00:00');
        $lastSeen = Carbon::parse('2026-09-07 11:59:00');

        $action = $this->createMock(CountInvalidTraceBufferSinceAction::class);
        $action->expects($this->once())
            ->method('handle')
            ->with($lastSeen)
            ->willReturn(0);

        new InvalidBufferGrownChecker($action)->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(),
                lastCheckedAt: $lastSeen,
                cooldownSeconds: 600
            ),
            new WatcherCheckContextObject($now, null)
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

        return new InvalidBufferGrownChecker($action)->check(
            $this->watcher(
                WatcherTypeEnum::InvalidBufferGrown,
                new InvalidBufferGrownSettingsObject(threshold: $threshold),
                lastCheckedAt: Carbon::now()->subMinute()
            ),
            new WatcherCheckContextObject(Carbon::now(), null)
        );
    }
}
