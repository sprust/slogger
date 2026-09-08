<?php

declare(strict_types=1);

namespace App\Modules\Notification\Entities;

use App\Modules\Notification\Enums\NotificationKindEnum;
use Illuminate\Support\Carbon;

readonly class NotificationObject
{
    public function __construct(
        public string $id,
        public int $channelId,
        public ?int $watcherId,
        public ?string $incidentId,
        public NotificationKindEnum $kind,
        public string $text,
        public ?Carbon $sentAt,
        public ?string $error,
        public Carbon $createdAt
    ) {
    }
}
