<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Services\Senders;

use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\SendResultObject;

interface NotificationSenderInterface
{
    public function send(ChannelObject $channel, string $text): SendResultObject;

    public function escape(string $value): string;

    public function bold(string $value): string;

    public function code(string $value): string;
}
