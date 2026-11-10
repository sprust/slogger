<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;

class DispatchNotificationListener
{
    public function __construct(private readonly DispatcherInterface $dispatcher)
    {
    }

    public function handle(NotificationEnqueuedEvent $event): void
    {
        $this->dispatcher->dispatch(new SendNotificationJob($event->notificationId));
    }
}
