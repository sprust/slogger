<?php

namespace Tests\Modules\Notification\Domain\Services;

use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\Types\BufferOverflowWatcherType;
use App\Modules\Watcher\Domain\Services\Types\InvalidBufferGrownWatcherType;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\SlowTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\TracesSpikeWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class IncidentMessageFactoryTest extends TestCase
{
    public function testAnOpenedIncidentIsATitleAHeadAndTheDetails(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(),
            incident: $this->incident(),
            event: $this->event(settings: ['threshold' => 1000], measured: ['buffer_count' => 12000]),
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
            event: $this->event(measured: ['buffer_count' => 12000]),
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
            event: $this->event(measured: ['buffer_count' => 12000]),
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
            event: $this->event(
                settings: ['duration' => 10.0],
                measured: ['slowest' => 41.2],
                groups: [
                    ['type' => 'http', 'tags' => ['api'], 'count' => 3, 'duration_max' => 41.2, 'trace_id' => 'abc123'],
                    ['type' => 'job', 'tags' => [], 'count' => 1, 'duration_max' => 12.7, 'trace_id' => 'def456'],
                ]
            ),
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

    public function testEverythingThatCameFromOutsideIsEscaped(): void
    {
        $text = $this->factory()->make(
            kind: NotificationKindEnum::Opened,
            watcher: $this->watcher(name: 'prod <b>buffer</b> & co'),
            incident: $this->incident(),
            event: $this->event(groups: [['type' => '<i>http</i>', 'tags' => []]]),
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
            watcherTypes: new WatcherTypeRegistry(
                new BufferOverflowWatcherType($this->createMock(BufferOverflowChecker::class)),
                new InvalidBufferGrownWatcherType($this->createMock(InvalidBufferGrownChecker::class)),
                new NoNewTracesWatcherType($this->createMock(NoNewTracesChecker::class)),
                new TracesSpikeWatcherType($this->createMock(TracesSpikeChecker::class)),
                new SlowTracesWatcherType($this->createMock(SlowTracesChecker::class))
            )
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

    /**
     * @param array<string, scalar>            $settings
     * @param array<string, scalar>            $measured
     * @param array<int, array<string, mixed>> $groups
     */
    private function event(array $settings = [], array $measured = [], array $groups = []): WatcherIncidentEventObject
    {
        return new WatcherIncidentEventObject(
            id: '68be1f000000000000000010',
            incidentId: '68be1f000000000000000009',
            settings: $settings,
            measured: $measured,
            groups: $groups,
            occurredAt: Carbon::parse('2026-09-08 19:20:03')
        );
    }
}
