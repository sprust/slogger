<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Notification\Domain\Actions\Mutations\EnqueueNotificationsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class EnqueueNotificationsListener
{
    public function __construct(
        private FindWatcherAction $findWatcherAction,
        private FindIncidentEventsAction $findIncidentEventsAction,
        private EnqueueNotificationsAction $enqueueNotificationsAction,
        private LoggerInterface $logger
    ) {
    }

    public function handle(WatcherIncidentChangedEvent $event): void
    {
        try {
            $watcher = $this->findWatcherAction->handle($event->incident->watcherId);

            if (is_null($watcher)) {
                return;
            }

            $this->enqueueNotificationsAction->handle(
                watcher: $watcher,
                incident: $event->incident,
                event: $this->findIncidentEventsAction->handle(
                    incidentId: $event->incident->id,
                    type: $watcher->type,
                    page: 1,
                    perPage: 1
                )[0] ?? null
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'failed to enqueue notifications for incident [%s]: %s',
                    $event->incident->id,
                    $exception->getMessage()
                )
            );
        }
    }
}
