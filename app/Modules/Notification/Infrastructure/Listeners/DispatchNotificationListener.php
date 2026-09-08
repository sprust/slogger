<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;

class DispatchNotificationListener
{
    public function handle(NotificationEnqueuedEvent $event): void
    {
        dispatch(new SendNotificationJob($event->notificationId));
    }
}
