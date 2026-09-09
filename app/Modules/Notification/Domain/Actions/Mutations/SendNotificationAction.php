<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Mutations;

use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Repositories\NotificationRepository;
use Illuminate\Support\Carbon;

readonly class SendNotificationAction
{
    public function __construct(
        private NotificationChannelTypeRegistry $types,
        private NotificationRepository $notificationRepository
    ) {
    }

    public function handle(NotificationObject $notification, ?ChannelObject $channel): SendResultObject
    {
        if (is_null($channel) || !$channel->enabled) {
            $result = new SendResultObject(
                delivered: false,
                permanent: true,
                error: sprintf(
                    is_null($channel) ? 'Channel [%d] is gone' : 'Channel [%d] is disabled',
                    $notification->channelId
                )
            );

            $this->notificationRepository->markFailed($notification->id, (string) $result->error);

            return $result;
        }

        $result = $this->types->for($channel->type)->sender()->send(
            channel: $channel,
            text: $notification->text
        );

        if ($result->delivered) {
            $this->notificationRepository->markSent(
                id: $notification->id,
                sentAt: Carbon::now()
            );

            return $result;
        }

        $this->notificationRepository->markFailed(
            id: $notification->id,
            error: $result->error ?? 'unknown error'
        );

        return $result;
    }
}
