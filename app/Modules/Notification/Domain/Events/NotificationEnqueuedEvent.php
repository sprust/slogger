<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Events;

readonly class NotificationEnqueuedEvent
{
    public function __construct(
        public string $notificationId
    ) {
    }
}
