<?php

namespace Tests\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Notification\Domain\UseCases\EnqueueIncidentNotificationsUseCase;
use App\Modules\Notification\Domain\Actions\Mutations\EnqueueNotificationsAction;
use App\Modules\Notification\Infrastructure\Listeners\EnqueueNotificationsListener;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Modules\Watcher\WatcherIncidentEventFactoryTrait;

class EnqueueNotificationsListenerTest extends TestCase
{
    use \Tests\Modules\Notification\IncidentParametersFactoryTrait;

    use WatcherIncidentEventFactoryTrait;

    public function testTheWatcherAndItsLatestEventReachTheAction(): void
    {
        $event        = $this->incidentEvent();
        $eventsAction = $this->createMock(FindIncidentEventsAction::class);

        $eventsAction
            ->method('handle')
            ->with(
                '68be1f000000000000000009',
                WatcherTypeEnum::BufferOverflow,
                1,
                1
            )
            ->willReturn([$event]);

        $enqueueAction = $this->createMock(EnqueueNotificationsAction::class);

        $enqueueAction
            ->expects(
                $this->once()
            )
            ->method('handle')
            ->with(
                $this->notificationWatcher(
                    $this->watcher()
                ),
                $this->notificationIncident(
                    $this->incident()
                ),
                $this->notificationEvent(
                    event: $event,
                    type: $this->watcher()->type,
                )
            );

        $this
            ->listener(
                watcher: $this->watcher(),
                eventsAction: $eventsAction,
                enqueueAction: $enqueueAction,
            )
            ->handle(
                new WatcherIncidentChangedEvent(
                    $this->incident()
                )
            );
    }

    public function testAnIncidentWithNoEventsYetIsStillHandedOver(): void
    {
        $eventsAction = $this->createMock(FindIncidentEventsAction::class);

        $eventsAction->method('handle')->willReturn([]);

        $enqueueAction = $this->createMock(EnqueueNotificationsAction::class);

        $enqueueAction
            ->expects(
                $this->once()
            )
            ->method('handle')
            ->with(
                $this->notificationWatcher(
                    $this->watcher()
                ),
                $this->notificationIncident(
                    $this->incident()
                ),
                $this->notificationEvent(
                    event: null,
                    type: $this->watcher()->type,
                )
            );

        $this
            ->listener(
                watcher: $this->watcher(),
                eventsAction: $eventsAction,
                enqueueAction: $enqueueAction,
            )
            ->handle(
                new WatcherIncidentChangedEvent(
                    $this->incident()
                )
            );
    }

    public function testAnIncidentOfADeletedWatcherSaysNothing(): void
    {
        $enqueueAction = $this->createMock(EnqueueNotificationsAction::class);

        $enqueueAction->expects(
            $this->never()
        )->method('handle');

        $this
            ->listener(
                watcher: null,
                eventsAction: $this->createMock(FindIncidentEventsAction::class),
                enqueueAction: $enqueueAction,
            )
            ->handle(
                new WatcherIncidentChangedEvent(
                    $this->incident()
                )
            );
    }

    public function testAFailureToEnqueueDoesNotEscapeIntoTheCheck(): void
    {
        $enqueueAction = $this->createMock(EnqueueNotificationsAction::class);

        $enqueueAction
            ->method('handle')
            ->willThrowException(
                new RuntimeException(
                    message: 'mongo is down',
                )
            );

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(
            $this->once()
        )->method('error');

        $listener = new EnqueueNotificationsListener(
            enqueueIncidentNotificationsUseCase: new EnqueueIncidentNotificationsUseCase(
                new \App\Modules\Notification\Domain\Actions\Mutations\EnqueueIncidentNotificationsAction(
                    findWatcherAction: $this->findWatcherAction(
                        $this->watcher()
                    ),
                    findIncidentEventsAction: $this->createMock(FindIncidentEventsAction::class),
                    watcherTypeRegistry: $this->watcherTypeRegistry(),
                    incidentParametersTransport: new \App\Modules\Notification\Transports\IncidentParametersTransport(),
                    enqueueNotificationsAction: $enqueueAction,
                )
            ),
            logger: $logger,
        );

        $listener->handle(
            new WatcherIncidentChangedEvent(
                $this->incident()
            )
        );
    }

    private function listener(
        ?WatcherObject $watcher,
        FindIncidentEventsAction $eventsAction,
        EnqueueNotificationsAction $enqueueAction
    ): EnqueueNotificationsListener {
        return new EnqueueNotificationsListener(
            enqueueIncidentNotificationsUseCase: new EnqueueIncidentNotificationsUseCase(
                new \App\Modules\Notification\Domain\Actions\Mutations\EnqueueIncidentNotificationsAction(
                    findWatcherAction: $this->findWatcherAction($watcher),
                    findIncidentEventsAction: $eventsAction,
                    watcherTypeRegistry: $this->watcherTypeRegistry(),
                    incidentParametersTransport: new \App\Modules\Notification\Transports\IncidentParametersTransport(),
                    enqueueNotificationsAction: $enqueueAction,
                )
            ),
            logger: $this->createMock(LoggerInterface::class),
        );
    }

    private function findWatcherAction(?WatcherObject $watcher): FindWatcherAction
    {
        $action = $this->createMock(FindWatcherAction::class);

        $action->method('handle')->willReturn($watcher);

        return $action;
    }

    private function watcher(): WatcherObject
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
            notificationChannelId: null,
            notifyOnOpened: true,
            notifyOnEvent: false,
            notifyOnClosed: true,
            settings: new BufferOverflowSettingsObject(),
            match: null,
            collectSince: $now,
            lastCheckedAt: $now,
            lastTriggeredAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function incident(): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: '68be1f000000000000000009',
            watcherId: 7,
            status: WatcherIncidentStatusEnum::Opened,
            firstEventAt: Carbon::parse(
                time: '2026-09-08 19:00:00',
            ),
            lastEventAt: Carbon::parse(
                time: '2026-09-08 19:20:03',
            ),
            eventsCount: 1,
            closedAt: null,
            closedByUserId: null,
        );
    }
}
