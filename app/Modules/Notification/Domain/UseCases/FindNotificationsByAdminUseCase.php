<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationsAction;

readonly class FindNotificationsByAdminUseCase
{
    public function __construct(private FindNotificationsAction $findNotificationsAction)
    {
    }

    /**
     * @return NotificationObject[]
     */
    public function handle(int $channelId, int $limit): array
    {
        return $this->findNotificationsAction->handle(
            channelId: $channelId,
            limit: $limit,
        );
    }
}
