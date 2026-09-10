<?php

namespace Tests\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Watcher\Entities\Events\SlowTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\SlowTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Entities\Events\ManyTracesEventSettingsObject;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Tests\Modules\Watcher\WatcherIncidentEventFactoryTrait;
use Tests\Modules\Watcher\WatcherTypeRegistryFactoryTrait;

class IncidentMessageFactoryTest extends TestCase
{
    use WatcherIncidentEventFactoryTrait;
    use WatcherTypeRegistryFactoryTrait;

    public function testAnOpenedIncidentIsATitleAHeadAndTheDetails(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(),
            incident: $this->incident(),
            event: $this->incidentEvent(),
            sender: $this->sender()
        );

        $this->assertSame(
            "🔴 <b>slogger (opened)</b>\n"
            . "\n"
            . "🏷 name: prod buffer\n"
            . "📛 type: Buffer overflow\n"
            . "🕒 lastEvent: 2026-09-08 19:20:03\n"
            . "\n"
            . "⚙️ settings:\n"
            . "• threshold: 1000\n"
            . "\n"
            . "📊 measured:\n"
            . '• buffer count: 12000',
            $text
        );
    }

    public function testARepeatSaysWhichEventOfTheIncidentItIs(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Event,
            watcher: $this->watcher(),
            incident: $this->incident(eventsCount: 4),
            event: $this->incidentEvent(),
            sender: $this->sender()
        );

        $this->assertStringContainsString('🟠 <b>slogger (event)</b>', $text);
        $this->assertStringContainsString('🔁 event: 4 of this incident', $text);
    }

    public function testAClosedIncidentSaysHowMuchOfItThereWas(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Closed,
            watcher: $this->watcher(),
            incident: $this->incident(
                status: WatcherIncidentStatusEnum::Closed,
                eventsCount: 4,
                closedAt: Carbon::parse('2026-09-08 20:00:00')
            ),
            event: $this->incidentEvent(),
            sender: $this->sender()
        );

        $this->assertSame(
            "🟢 <b>slogger (closed)</b>\n"
            . "\n"
            . "🏷 name: prod buffer\n"
            . "📛 type: Buffer overflow\n"
            . "✅ closedAt: 2026-09-08 20:00:00\n"
            . "🕒 firstEvent: 2026-09-08 19:00:00\n"
            . '🔢 events: 4',
            $text
        );
    }

    public function testAnEventWithoutAPayloadIsStillAMessage(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(),
            incident: $this->incident(),
            event: null,
            sender: $this->sender()
        );

        $this->assertStringContainsString('🔴 <b>slogger (opened)</b>', $text);
        $this->assertStringContainsString('🏷 name: prod buffer', $text);
        $this->assertStringContainsString('📛 type: Buffer overflow', $text);
        $this->assertStringNotContainsString('measured:', $text);
    }

    public function testTheTracesBlockCarriesTheShapeItsCountAndTheTraceWorthOpening(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(type: WatcherTypeEnum::SlowTraces),
            incident: $this->incident(),
            event: $this->incidentEvent(new SlowTracesEventPayloadObject(
                settings: new SlowTracesEventSettingsObject(duration: 10.0, windowMinutes: 5),
                measured: new SlowTracesEventMeasuredObject(slowest: 41.2),
                groups: [
                    $this->eventGroup(
                        type: 'http',
                        tags: ['api'],
                        count: 3,
                        durationMax: 41.2,
                        slowestTraceId: 'abc123'
                    ),
                    $this->eventGroup(
                        type: 'job',
                        count: 1,
                        durationMax: 12.7,
                        slowestTraceId: 'def456'
                    ),
                ]
            )),
            sender: $this->sender()
        );

        $this->assertStringContainsString('🔴 <b>slogger (opened)</b>', $text);
        $this->assertStringContainsString('📛 type: Slow traces', $text);
        $this->assertStringContainsString("⚙️ settings:\n• duration: 10s", $text);
        $this->assertStringContainsString("📊 measured:\n• slowest: 41.2s", $text);
        $this->assertStringContainsString(
            "🔎 traces:\n• http api\n  3 traces, up to 41.2s\n  <code>abc123</code>",
            $text
        );
        $this->assertStringContainsString("• job\n  1 trace, up to 12.7s\n  <code>def456</code>", $text);
    }

    /**
     * Only slow_traces puts a maximum in a group. The groups of a counting watcher carry
     * a count and nothing else, and the count used to be printed only beside a duration —
     * so every such message listed the shapes without ever saying how many traces were
     * behind them, which is the whole of what the watcher is about.
     */
    public function testACountingGroupWithoutADurationStillSaysHowManyTracesThereWere(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(type: WatcherTypeEnum::ManyTraces),
            incident: $this->incident(),
            event: $this->incidentEvent(new ManyTracesEventPayloadObject(
                settings: new ManyTracesEventSettingsObject(windowMinutes: 5, threshold: 500),
                measured: new ManyTracesEventMeasuredObject(windowCount: 900),
                groups: [
                    $this->eventGroup(type: 'http', tags: ['api'], count: 700),
                    $this->eventGroup(type: 'job', count: 1),
                ]
            )),
            sender: $this->sender()
        );

        $this->assertStringContainsString("🔎 traces:\n• http api\n  700 traces", $text);
        $this->assertStringContainsString("• job\n  1 trace", $text);
        $this->assertStringNotContainsString('up to', $text);
    }

    public function testEverythingThatCameFromOutsideIsEscaped(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(type: WatcherTypeEnum::SlowTraces, name: 'prod <b>buffer</b> & co'),
            incident: $this->incident(),
            event: $this->incidentEvent(new SlowTracesEventPayloadObject(
                settings: new SlowTracesEventSettingsObject(duration: 10.0, windowMinutes: 5),
                measured: new SlowTracesEventMeasuredObject(slowest: 41.2),
                groups: [$this->eventGroup(type: '<i>http</i>')]
            )),
            sender: $this->sender()
        );

        $this->assertStringContainsString('🏷 name: prod &lt;b&gt;buffer&lt;/b&gt; &amp; co', $text);
        $this->assertStringContainsString('&lt;i&gt;http&lt;/i&gt;', $text);
        $this->assertStringNotContainsString('<i>', $text);
    }

    private function factory(): IncidentMessageFactory
    {
        return new IncidentMessageFactory(
            appName: 'slogger',
            watcherTypes: $this->watcherTypeRegistry()
        );
    }

    private function sender(): TelegramSender
    {
        return new TelegramSender($this->createMock(ClientInterface::class));
    }

    private function watcher(
        string $name = 'prod buffer',
        WatcherTypeEnum $type = WatcherTypeEnum::BufferOverflow
    ): WatcherObject {
        $now = Carbon::parse('2026-09-08 19:00:00');

        return new WatcherObject(
            id: 7,
            name: $name,
            type: $type,
            enabled: true,
            cooldownSeconds: 600,
            notificationChannelId: null,
            settings: new BufferOverflowSettingsObject(),
            match: null,
            collectSince: $now,
            lastCheckedAt: $now,
            lastTriggeredAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function incident(
        WatcherIncidentStatusEnum $status = WatcherIncidentStatusEnum::Opened,
        int $eventsCount = 1,
        ?Carbon $closedAt = null
    ): WatcherIncidentObject {
        return new WatcherIncidentObject(
            id: '68be1f000000000000000009',
            watcherId: 7,
            status: $status,
            firstEventAt: Carbon::parse('2026-09-08 19:00:00'),
            lastEventAt: Carbon::parse('2026-09-08 19:20:03'),
            eventsCount: $eventsCount,
            closedAt: $closedAt,
            closedByUserId: null
        );
    }
}
