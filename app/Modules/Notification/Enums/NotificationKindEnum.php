<?php

declare(strict_types=1);

namespace App\Modules\Notification\Enums;

enum NotificationKindEnum: string
{
    case Opened = 'opened';
    case Event = 'event';
    case Closed = 'closed';
}
