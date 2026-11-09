<?php

namespace Tests\Modules\Notification\Domain\Actions;

use App\Modules\Notification\Domain\Actions\Mutations\EnqueueNotificationsAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
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
use App\Modules\Common\Domain\Services\DispatcherInterface as Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Notification\NotificationFactoryTrait;
use RuntimeException;

class EnqueueNotificationsActionTest extends TestCase
{
    use \Tests\Modules\Notification\IncidentParametersFactoryTrait;

    use NotificationFactoryTrait;

    private Dispatcher $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->events = $this->createMock(Dispatcher::class);
    }

    public function testTheFirstEventOfAnIncidentIsNews(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                7,
                '68be1f000000000000000009',
                NotificationKindEnum::Opened
            )
            ->willReturn($this->notification());

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher($this->watcher()),
                incident: $this->notificationIncident($this->incident()),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                ),
            );
    }

    public function testAFurtherEventIsARepeat(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                7,
                '68be1f000000000000000009',
                NotificationKindEnum::Event
            )
            ->willReturn($this->notification());

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher(
                    $this->watcher(
                        notifyOnEvent: true,
                    )
                ),
                incident: $this->notificationIncident(
                    $this->incident(
                        eventsCount: 3,
                    )
                ),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher(
                        notifyOnEvent: true,
                    )->type,
                ),
            );
    }

    public function testAClosedIncidentIsAClosing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with(
                1,
                7,
                '68be1f000000000000000009',
                NotificationKindEnum::Closed
            )
            ->willReturn($this->notification());

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher($this->watcher()),
                incident: $this->notificationIncident(
                    $this->incident(
                        status: WatcherIncidentStatusEnum::Closed,
                        eventsCount: 3,
                    )
                ),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                ),
            );
    }

    public function testAWatcherThatDoesNotWantOpeningsSaysNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher(
                    $this->watcher(
                        notifyOnOpened: false,
                    )
                ),
                incident: $this->notificationIncident($this->incident()),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher(
                        notifyOnOpened: false,
                    )->type,
                ),
            );
    }

    public function testAWatcherThatDoesNotWantRepeatsSaysNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher(
                    $this->watcher(
                        notifyOnEvent: false,
                    )
                ),
                incident: $this->notificationIncident(
                    $this->incident(
                        eventsCount: 3,
                    )
                ),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher(
                        notifyOnEvent: false,
                    )->type,
                ),
            );
    }

    public function testAWatcherThatDoesNotWantClosingsSaysNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher(
                    $this->watcher(
                        notifyOnClosed: false,
                    )
                ),
                incident: $this->notificationIncident(
                    $this->incident(
                        status: WatcherIncidentStatusEnum::Closed,
                    )
                ),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher(
                        notifyOnClosed: false,
                    )->type,
                ),
            );
    }

    /** Nothing is read either: there is no channel to look up. */
    public function testAWatcherThatNamesNoChannelSaysNothing(): void
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);

        $findChannelAction->expects($this->never())->method('handle');

        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this->events->expects($this->never())->method('event');

        (new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->channelTypes(),
            messageFactory: $this->createMock(IncidentMessageFactory::class),
            notificationRepository: $repository,
            dispatcher: $this->events,
        ))->handle(
            watcher: $this->notificationWatcher(
                $this->watcher(
                    channelId: null,
                )
            ),
            incident: $this->notificationIncident($this->incident()),
            event: $this->notificationEvent(
                event: null,
                type: $this->watcher(
                    channelId: null,
                )->type,
            ),
        );
    }

    public function testTheChannelAskedForIsTheOneTheWatcherNames(): void
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);

        $findChannelAction
            ->expects($this->once())
            ->method('handle')
            ->with(4)
            ->willReturn(
                $this->channel(
                    id: 4,
                )
            );

        $repository = $this->createMock(NotificationRepository::class);

        $repository->method('create')->willReturn($this->notification());

        $messageFactory = $this->createMock(IncidentMessageFactory::class);

        $messageFactory->method('make')->willReturn('a watcher went off');

        (new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->channelTypes(),
            messageFactory: $messageFactory,
            notificationRepository: $repository,
            dispatcher: $this->events,
        ))->handle(
            watcher: $this->notificationWatcher(
                $this->watcher(
                    channelId: 4,
                )
            ),
            incident: $this->notificationIncident($this->incident()),
            event: $this->notificationEvent(
                event: null,
                type: $this->watcher(
                    channelId: 4,
                )->type,
            ),
        );
    }

    /** Quiet rather than a row SendNotificationAction would only mark failed. */
    public function testAChannelThatWasSwitchedOffGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(
                    enabled: false,
                ),
            )
            ->handle(
                watcher: $this->notificationWatcher($this->watcher()),
                incident: $this->notificationIncident($this->incident()),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                ),
            );
    }

    /** Removed since the watcher was pointed at it. */
    public function testAChannelThatIsGoneGetsNothing(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->never())->method('create');

        $this
            ->action(
                repository: $repository,
                channel: null,
            )
            ->handle(
                watcher: $this->notificationWatcher($this->watcher()),
                incident: $this->notificationIncident($this->incident()),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                ),
            );
    }

    public function testTheRowIsAnnouncedByItsOwnEvent(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $this->events
            ->expects($this->once())
            ->method('event')
            ->with(new NotificationEnqueuedEvent('68be1f000000000000000001'));

        $this
            ->action(
                repository: $repository,
                channel: $this->channel(),
            )
            ->handle(
                watcher: $this->notificationWatcher($this->watcher()),
                incident: $this->notificationIncident($this->incident()),
                event: $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                ),
            );
    }

    public function testTheRowIsWrittenBeforeAnythingIsQueued(): void
    {
        $written    = 0;
        $repository = $this->createMock(NotificationRepository::class);

        $repository
            ->method('create')
            ->willReturnCallback(function () use (&$written): NotificationObject {
                $written++;

                return $this->notification();
            });

        $this->events
            ->method('event')
            ->willThrowException(
                new RuntimeException(
                    message: 'broker is down',
                )
            );

        try {
            $this
                ->action(
                    repository: $repository,
                    channel: $this->channel(),
                )
                ->handle(
                    watcher: $this->notificationWatcher($this->watcher()),
                    incident: $this->notificationIncident($this->incident()),
                    event: $this->notificationEvent(
                        event: null,
                        type: $this->watcher()->type,
                    ),
                );
        } catch (RuntimeException) {
        }

        $this->assertSame(1, $written);
    }

    private function action(NotificationRepository $repository, ?ChannelObject $channel): EnqueueNotificationsAction
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);

        $findChannelAction->method('handle')->willReturn($channel);

        $messageFactory = $this->createMock(IncidentMessageFactory::class);

        $messageFactory->method('make')->willReturn('a watcher went off');

        $repository->method('create')->willReturn($this->notification());

        return new EnqueueNotificationsAction(
            findChannelAction: $findChannelAction,
            types: $this->channelTypes(),
            messageFactory: $messageFactory,
            notificationRepository: $repository,
            dispatcher: $this->events,
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
            createdAt: Carbon::parse(
                time: '2026-09-08 12:00:00',
            ),
        );
    }

    private function channel(int $id = 1, bool $enabled = true): ChannelObject
    {
        $now = Carbon::parse(
            time: '2026-09-08 12:00:00',
        );

        return new ChannelObject(
            id: $id,
            name: 'ops chat',
            type: NotificationChannelTypeEnum::Telegram,
            enabled: $enabled,
            settings: new TelegramSettingsObject(
                botToken: '123:abc',
                chatId: '-100500',
            ),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function watcher(?int $channelId = 1, bool $notifyOnOpened = true, bool $notifyOnEvent = false, bool $notifyOnClosed = true): WatcherObject
    {
        $now = Carbon::parse(
            time: '2026-09-08 19:00:00',
        );

        return new WatcherObject(
            id: 7,
            name: 'prod buffer',
            type: WatcherTypeEnum::BufferOverflow,
            enabled: true,
            cooldownSeconds: 600,
            notificationChannelId: $channelId,
            notifyOnOpened: $notifyOnOpened,
            notifyOnEvent: $notifyOnEvent,
            notifyOnClosed: $notifyOnClosed,
            settings: new BufferOverflowSettingsObject(),
            match: null,
            collectSince: $now,
            lastCheckedAt: $now,
            lastTriggeredAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function incident(WatcherIncidentStatusEnum $status = WatcherIncidentStatusEnum::Opened, int $eventsCount = 1): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: '68be1f000000000000000009',
            watcherId: 7,
            status: $status,
            firstEventAt: Carbon::parse(
                time: '2026-09-08 19:00:00',
            ),
            lastEventAt: Carbon::parse(
                time: '2026-09-08 19:20:03',
            ),
            eventsCount: $eventsCount,
            closedAt: $status === WatcherIncidentStatusEnum::Closed ? Carbon::parse(
                time: '2026-09-08 20:00:00',
            ) : null,
            closedByUserId: null,
        );
    }
}
