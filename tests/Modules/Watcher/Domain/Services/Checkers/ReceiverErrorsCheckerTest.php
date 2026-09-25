<?php

namespace Tests\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Logs\Domain\Actions\FindReceiverErrorStatAction;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Watcher\Domain\Services\Checkers\ReceiverErrorsChecker;
use App\Modules\Watcher\Domain\Services\WatcherCountWindow;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class ReceiverErrorsCheckerTest extends TestCase
{
    use WatcherFactoryTrait;

    public function testErrorsAtTheThresholdAreReported(): void
    {
        $payload = $this->check(count: 3, threshold: 3, lastMessage: 'dial tcp: connection refused');

        $this->assertInstanceOf(LogErrorsEventPayloadObject::class, $payload);
        $this->assertSame(3, $payload->settings->threshold);
        $this->assertSame(3, $payload->measured->errorCount);
        $this->assertSame('2026-09-07 11:50:00', $payload->measured->since);
        $this->assertSame('dial tcp: connection refused', $payload->measured->lastMessage);
    }

    public function testFewerErrorsThanTheThresholdSayNothing(): void
    {
        $this->assertNull($this->check(count: 2, threshold: 3));
    }

    public function testALongMessageIsCut(): void
    {
        $payload = $this->check(count: 1, threshold: 1, lastMessage: str_repeat('x', 2000));

        $this->assertInstanceOf(LogErrorsEventPayloadObject::class, $payload);
        $this->assertSame(ReceiverErrorsChecker::MAX_MESSAGE_LENGTH, mb_strwidth($payload->measured->lastMessage));
    }

    private function check(int $count, int $threshold, ?string $lastMessage = 'error'): mixed
    {
        $action = $this->createMock(FindReceiverErrorStatAction::class);
        $action->method('handle')->willReturn(
            new LogLevelStatObject(count: $count, lastMessage: $lastMessage)
        );

        return new ReceiverErrorsChecker($action, new WatcherCountWindow())->check(
            $this->watcher(
                WatcherTypeEnum::ReceiverErrors,
                new LogErrorsSettingsObject(threshold: $threshold),
                lastTriggeredAt: Carbon::parse('2026-09-07 11:50:00')
            ),
            new WatcherCheckContextObject(Carbon::parse('2026-09-07 12:00:00'), null)
        );
    }
}
