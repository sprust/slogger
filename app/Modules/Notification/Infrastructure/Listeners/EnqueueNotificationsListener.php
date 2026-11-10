<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Notification\Domain\UseCases\EnqueueIncidentNotificationsUseCase;
use App\Modules\Notification\Parameters\IncidentSnapshotParameters;
use App\Modules\Notification\Enums\IncidentStatusEnum;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class EnqueueNotificationsListener
{
    public function __construct(
        private EnqueueIncidentNotificationsUseCase $enqueueIncidentNotificationsUseCase,
        private LoggerInterface $logger
    ) {
    }

    public function handle(WatcherIncidentChangedEvent $event): void
    {
        try {
            $incident = $event->incident;

            $this->enqueueIncidentNotificationsUseCase->handle(
                new IncidentSnapshotParameters(
                    id: $incident->id,
                    watcherId: $incident->watcherId,
                    status: IncidentStatusEnum::from($incident->status->value),
                    firstEventAt: $incident->firstEventAt,
                    lastEventAt: $incident->lastEventAt,
                    eventsCount: $incident->eventsCount,
                    closedAt: $incident->closedAt,
                    closedByUserId: $incident->closedByUserId,
                )
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                message: sprintf(
                    'failed to enqueue notifications for incident [%s]: %s',
                    $event->incident->id,
                    $exception->getMessage()
                ),
            );
        }
    }
}
