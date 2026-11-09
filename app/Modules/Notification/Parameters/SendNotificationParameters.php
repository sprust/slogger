<?php

declare(strict_types=1);

namespace App\Modules\Notification\Parameters;

use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Entities\ChannelObject;
use JsonSerializable;

readonly class SendNotificationParameters implements JsonSerializable
{
    public function __construct(public NotificationObject $notification, public ?ChannelObject $channel)
    {
    }

    /** @return array{notificationId: string} */
    public function jsonSerialize(): array
    {
        return [
            'notificationId' => $this->notification->id,
        ];
    }
}
