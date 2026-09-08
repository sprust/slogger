<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Exceptions;

use Exception;

class NotificationChannelNotFoundException extends Exception
{
    public function __construct(int $channelId)
    {
        parent::__construct("Notification channel [$channelId] not found");
    }
}
