<?php

namespace Tests\Modules\Notification\Domain\Actions;

use App\Modules\Notification\Domain\Actions\Mutations\EnqueueNotificationsAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Entities\Settings\TelegramSettingsObject;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnqueueNotificationsActionTest extends TestCase
{
    private Dispatcher $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = $this->createMock(Dispatcher::class);
    }

    public function testTheFirstEventOfAnIncidentIsNews(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('create')
            ->with(1, 7, '68be1f000000000000000009', NotificationKindEnum::Opened)
            ->willReturn($this->notification());

        $this->action($repository, $this->channel())
            ->handle($this->watcher(), $this->incident(), null);
    }

    public function testAFurtherEventIsARepeat(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('create')
            ->with(1, 7, '68be1f000000000000000009', NotificationKindEnum::Event)
            ->willReturn($this->notification());

        $this->action($repository, $this->channel(onEvent: true))
            ->handle($this->watcher(), $this->incident(eventsCount: 3), null);
    }

    public function testAClosedIncidentIsAClosing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('create')
            ->with(1, 7, '68be1f000000000000000009', NotificationKindEnum::Closed)
            ->willReturn($this->notification());

        $this->action($repository, $this->channel())
            ->handle(
                $this->watcher(),
                $this->incident(status: WatcherIncidentStatusEnum::Closed, eventsCount: 3),
                null
            );
    }

    public function testAChannelThatDoesNotWantRepeatsGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->never())->method('create');

        $this->action($repository, $this->channel(onEvent: false))
            ->handle($this->watcher(), $this->incident(eventsCount: 3), null);
    }

    public function testAChannelThatDoesNotWantClosingsGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->never())->method('create');

        $this->action($repository, $this->channel(onClosed: false))
            ->handle(
                $this->watcher(),
                $this->incident(status: WatcherIncidentStatusEnum::Closed),
                null
            );
    }

    /** Nothing is read either: there is no channel to look up. */
    public function testAWatcherThatNamesNoChannelSaysNothing(): void
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);
        $findChannelAction->expects($this->never())->method('handle');

        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->never())->method('create');

        $this->events->expects($this->never())->method('dispatch');

        new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->types(),
            messageFactory: $this->createMock(IncidentMessageFactory::class),
            notificationRepository: $repository,
            events: $this->events
        )->handle($this->watcher(channelId: null), $this->incident(), null);
    }

    public function testTheChannelAskedForIsTheOneTheWatcherNames(): void
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);

        $findChannelAction->expects($this->once())
            ->method('handle')
            ->with(4)
            ->willReturn($this->channel(id: 4));

        $repository = $this->createMock(NotificationRepository::class);
        $repository->method('create')->willReturn($this->notification());

        $messageFactory = $this->createMock(IncidentMessageFactory::class);
        $messageFactory->method('make')->willReturn('a watcher went off');

        new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->types(),
            messageFactory: $messageFactory,
            notificationRepository: $repository,
            events: $this->events
        )->handle($this->watcher(channelId: 4), $this->incident(), null);
    }

    /** Quiet rather than a row SendNotificationAction would only mark failed. */
    public function testAChannelThatWasSwitchedOffGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->never())->method('create');

        $this->action($repository, $this->channel(enabled: false))
            ->handle($this->watcher(), $this->incident(), null);
    }

    /** Removed since the watcher was pointed at it. */
    public function testAChannelThatIsGoneGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);
        $repository->expects($this->never())->method('create');

        $this->action($repository, null)
            ->handle($this->watcher(), $this->incident(), null);
    }

    public function testTheRowIsAnnouncedByItsOwnEvent(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $this->events->expects($this->once())
            ->method('dispatch')
            ->with(new NotificationEnqueuedEvent('68be1f000000000000000001'));

        $this->action($repository, $this->channel())
            ->handle($this->watcher(), $this->incident(), null);
    }

    public function testTheRowIsWrittenBeforeAnythingIsQueued(): void
    {
        $written = 0;

        $repository = $this->createMock(NotificationRepository::class);
        $repository->method('create')->willReturnCallback(function () use (&$written): NotificationObject {
            $written++;

            return $this->notification();
        });

        $this->events->method('dispatch')->willThrowException(new RuntimeException('broker is down'));

        try {
            $this->action($repository, $this->channel())
                ->handle($this->watcher(), $this->incident(), null);
        } catch (RuntimeException) {
        }

        $this->assertSame(1, $written);
    }

    private function action(
        NotificationRepository $repository,
        ?ChannelObject $channel
    ): EnqueueNotificationsAction {
        $findChannelAction = $this->createMock(FindChannelAction::class);
        $findChannelAction->method('handle')->willReturn($channel);

        $messageFactory = $this->createMock(IncidentMessageFactory::class);
        $messageFactory->method('make')->willReturn('a watcher went off');

        $repository->method('create')->willReturn($this->notification());

        return new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->types(),
            messageFactory: $messageFactory,
            notificationRepository: $repository,
            events: $this->events
        );
    }

    private function notification(): NotificationObject
    {
        return new NotificationObject(
            id: '68be1f000000000000000001',
            channelId: 1,
            watcherId: 7,
            incidentId: '68be1f000000000000000009',
            kind: NotificationKindEnum::Opened,
            text: 'a watcher went off',
            sentAt: null,
            error: null,
            createdAt: Carbon::parse('2026-09-08 12:00:00')
        );
    }

    private function types(): NotificationChannelTypeRegistry
    {
        return new NotificationChannelTypeRegistry(
            new TelegramChannelType($this->createMock(TelegramSender::class))
        );
    }

    private function channel(
        int $id = 1,
        bool $onEvent = false,
        bool $onClosed = true,
        bool $enabled = true
    ): ChannelObject {
        $now = Carbon::parse('2026-09-08 12:00:00');

        return new ChannelObject(
            id: $id,
            name: 'ops chat',
            type: NotificationChannelTypeEnum::Telegram,
            enabled: $enabled,
            onOpened: true,
            onEvent: $onEvent,
            onClosed: $onClosed,
            settings: new TelegramSettingsObject(botToken: '123:abc', chatId: '-100500'),
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function watcher(?int $channelId = 1): WatcherObject
    {
        $now = Carbon::parse('2026-09-08 19:00:00');

        return new WatcherObject(
            id: 7,
            name: 'prod buffer',
            type: WatcherTypeEnum::BufferOverflow,
            enabled: true,
            cooldownSeconds: 600,
            notificationChannelId: $channelId,
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
        int $eventsCount = 1
    ): WatcherIncidentObject {
        return new WatcherIncidentObject(
            id: '68be1f000000000000000009',
            watcherId: 7,
            status: $status,
            firstEventAt: Carbon::parse('2026-09-08 19:00:00'),
            lastEventAt: Carbon::parse('2026-09-08 19:20:03'),
            eventsCount: $eventsCount,
            closedAt: $status === WatcherIncidentStatusEnum::Closed
                ? Carbon::parse('2026-09-08 20:00:00')
                : null,
            closedByUserId: null
        );
    }
}
