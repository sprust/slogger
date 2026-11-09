<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Domain\Actions\Mutations\SendTestNotificationAction;

readonly class SendTestNotificationByAdminUseCase
{
    public function __construct(private SendTestNotificationAction $sendTestNotificationAction)
    {
    }

    /**
     * @throws NotificationChannelNotFoundException
     */
    public function handle(int $channelId): SendResultObject
    {
        return $this->sendTestNotificationAction->handle(
            channelId: $channelId,
        );
    }
}
