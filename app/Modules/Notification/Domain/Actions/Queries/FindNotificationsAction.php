<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Repositories\NotificationRepository;

readonly class FindNotificationsAction
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    /**
     * @return NotificationObject[]
     */
    public function handle(int $channelId, int $limit): array
    {
        return $this->notificationRepository->findByChannelId($channelId, $limit);
    }
}
