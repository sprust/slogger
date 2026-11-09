<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Domain\Actions\Mutations\SendNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Parameters\SendNotificationParameters;
use App\Modules\Notification\Entities\SendResultObject;

readonly class SendNotificationUseCase
{
    public function __construct(
        private SendNotificationAction $sendNotificationAction,
        private FindNotificationAction $findNotificationAction,
        private FindChannelAction $findChannelAction
    ) {
    }

    public function handle(string $notificationId): ?SendResultObject
    {
        $notification = $this->findNotificationAction->handle($notificationId);

        if ($notification === null || $notification->sentAt !== null) {
            return null;
        }

        return $this->sendNotificationAction->handle(
            new SendNotificationParameters(
                notification: $notification,
                channel: $this->findChannelAction->handle($notification->channelId),
            )
        );
    }
}
