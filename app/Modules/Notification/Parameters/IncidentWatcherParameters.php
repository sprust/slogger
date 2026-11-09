<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

readonly class IncidentWatcherParameters
{
    public function __construct(
        public int $id,
        public string $name,
        public string $typeTitle,
        public ?int $notificationChannelId,
        public bool $notifyOnOpened,
        public bool $notifyOnEvent,
        public bool $notifyOnClosed
    ) {
    }
}
