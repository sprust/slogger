<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Enums\NotificationKindEnum;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * Writes down what a watcher has to say, for the one channel it names. A watcher that
 * names none says nothing.
 */
readonly class EnqueueNotificationsAction
{
    public function __construct(
        private FindChannelAction $findChannelAction,
        private NotificationChannelTypeRegistry $types,
        private IncidentMessageFactory $messageFactory,
        private NotificationRepository $notificationRepository,
        private Dispatcher $events
    ) {
    }

    public function handle(
        WatcherObject $watcher,
        WatcherIncidentObject $incident,
        ?WatcherIncidentEventObject $event
    ): void {
        $channel = $this->channelOf($watcher);

        if (is_null($channel)) {
            return;
        }

        $kind = $this->kindOf($incident);

        if (!$this->speaksAbout($channel, $kind)) {
            return;
        }

        $notification = $this->notificationRepository->create(
            channelId: $channel->id,
            watcherId: $watcher->id,
            incidentId: $incident->id,
            kind: $kind,
            text: $this->messageFactory->make(
                kind: $kind,
                watcher: $watcher,
                incident: $incident,
                event: $event,
                sender: $this->types->for($channel->type)->sender()
            ),
            createdAt: Carbon::now()
        );

        // Written first and queued after: a broker that will not take the job still
        // leaves a row saying the message was meant to go.
        $this->events->dispatch(new NotificationEnqueuedEvent($notification->id));
    }

    /** One that was switched off or removed counts as none: the watcher goes quiet. */
    private function channelOf(WatcherObject $watcher): ?ChannelObject
    {
        if (is_null($watcher->notificationChannelId)) {
            return null;
        }

        $channel = $this->findChannelAction->handle($watcher->notificationChannelId);

        return $channel?->enabled === true ? $channel : null;
    }

    private function kindOf(WatcherIncidentObject $incident): NotificationKindEnum
    {
        if ($incident->status === WatcherIncidentStatusEnum::Closed) {
            return NotificationKindEnum::Closed;
        }

        return $incident->eventsCount > 1
            ? NotificationKindEnum::Event
            : NotificationKindEnum::Opened;
    }

    private function speaksAbout(ChannelObject $channel, NotificationKindEnum $kind): bool
    {
        return match ($kind) {
            NotificationKindEnum::Opened => $channel->onOpened,
            NotificationKindEnum::Event  => $channel->onEvent,
            NotificationKindEnum::Closed => $channel->onClosed,
        };
    }
}
