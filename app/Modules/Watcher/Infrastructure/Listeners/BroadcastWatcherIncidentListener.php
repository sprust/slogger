<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Listeners;

use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentStatAction;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Infrastructure\Broadcasting\WatcherIncidentBroadcast;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * The count is taken here rather than carried by the domain event: how many incidents
 * stand open is a reading of the table, not part of what happened to this one.
 */
readonly class BroadcastWatcherIncidentListener
{
    public function __construct(
        private FindIncidentStatAction $findIncidentStatAction,
        private Dispatcher $events,
    ) {
    }

    public function handle(WatcherIncidentChangedEvent $event): void
    {
        $this->events->dispatch(
            new WatcherIncidentBroadcast(
                incident: $event->incident,
                openedCount: $this->findIncidentStatAction->handle()->openedCount
            )
        );
    }
}
