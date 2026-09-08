<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Actions\Queries\FindChannelsAction;
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

readonly class EnqueueNotificationsAction
{
    public function __construct(
        private FindChannelsAction $findChannelsAction,
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
        $kind = $this->kindOf($incident);

        $now = Carbon::now();

        $notifications = [];

        foreach ($this->findChannelsAction->handle(enabled: true) as $channel) {
            if (!$this->speaksAbout($channel, $kind)) {
                continue;
            }

            $notifications[] = $this->notificationRepository->create(
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
                createdAt: $now
            );
        }

        // Every row is written before anything is put on the queue: a broker that refuses
        // the first would otherwise leave the channels after it with no record at all.
        foreach ($notifications as $notification) {
            $this->events->dispatch(new NotificationEnqueuedEvent($notification->id));
        }
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
