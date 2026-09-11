<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Watcher\Domain\Services\Checkers\LogErrorsChecker;
use App\Modules\Watcher\Domain\Services\WatcherCountWindow;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class LogErrorsCheckerTest extends TestCase
{
    use WatcherFactoryTrait;

    public function testErrorsAtTheThresholdAreReported(): void
    {
        $this->assertNotNull($this->check(count: 3, threshold: 3));
    }

    public function testFewerErrorsThanTheThresholdSayNothing(): void
    {
        $this->assertNull($this->check(count: 2, threshold: 3));
    }

    public function testNoErrorsSayNothing(): void
    {
        $this->assertNull($this->check(count: 0, threshold: 1, lastMessage: null));
    }

    public function testTheEventCarriesTheCountTheWindowAndTheLastMessage(): void
    {
        $payload = $this->check(count: 4, threshold: 1, lastMessage: 'connect: IO error');

        $this->assertInstanceOf(LogErrorsEventPayloadObject::class, $payload);
        $this->assertSame(1, $payload->settings->threshold);
        $this->assertSame(4, $payload->measured->errorCount);
        $this->assertSame('2026-09-07 11:50:00', $payload->measured->since);
        $this->assertSame('connect: IO error', $payload->measured->lastMessage);
    }

    public function testALongMessageIsCut(): void
    {
        $payload = $this->check(count: 1, threshold: 1, lastMessage: str_repeat('x', 2000));

        $this->assertInstanceOf(LogErrorsEventPayloadObject::class, $payload);
        $this->assertSame(LogErrorsChecker::MAX_MESSAGE_LENGTH, mb_strwidth($payload->measured->lastMessage));
        $this->assertStringEndsWith('…', $payload->measured->lastMessage);
    }

    public function testTheWindowRunsFromTheLastTriggerToNow(): void
    {
        $action = $this->createMock(FindLogErrorStatAction::class);
        $action->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(
                    static fn(Carbon $since): bool => $since->toDateTimeString() === '2026-09-07 11:50:00'
                ),
                $this->callback(
                    static fn(Carbon $until): bool => $until->toDateTimeString() === '2026-09-07 12:00:00'
                )
            )
            ->willReturn(new LogLevelStatObject(count: 0, lastMessage: null));

        new LogErrorsChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::LogErrors,
                new LogErrorsSettingsObject(),
                lastTriggeredAt: Carbon::parse('2026-09-07 11:50:00'),
                lastCheckedAt: Carbon::parse('2026-09-07 11:59:00'),
                cooldownSeconds: 600
            ),
            new WatcherCheckContextObject(Carbon::parse('2026-09-07 12:00:00'), null)
        );
    }

    private function check(int $count, int $threshold, ?string $lastMessage = 'error'): mixed
    {
        $action = $this->createMock(FindLogErrorStatAction::class);
        $action->method('handle')->willReturn(
            new LogLevelStatObject(count: $count, lastMessage: $lastMessage)
        );

        return new LogErrorsChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::LogErrors,
                new LogErrorsSettingsObject(threshold: $threshold),
                lastTriggeredAt: Carbon::parse('2026-09-07 11:50:00')
            ),
            new WatcherCheckContextObject(Carbon::parse('2026-09-07 12:00:00'), null)
        );
    }
}
