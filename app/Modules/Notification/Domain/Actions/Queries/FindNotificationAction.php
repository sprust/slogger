<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Actions\Queries;

use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Repositories\NotificationRepository;

readonly class FindNotificationAction
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
    }

    public function handle(string $id): ?NotificationObject
    {
        return $this->notificationRepository->findById($id);
    }
}
