<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Jobs;

use App\Modules\Notification\Domain\Actions\Mutations\SendNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 6;

    /** @var int[] */
    public array $backoff = [15, 60, 300, 900, 3600];

    public function __construct(
        private readonly string $notificationId
    ) {
    }

    public function handle(
        FindNotificationAction $findNotificationAction,
        FindChannelAction $findChannelAction,
        SendNotificationAction $sendNotificationAction
    ): void {
        $notification = $findNotificationAction->handle($this->notificationId);

        if (is_null($notification) || !is_null($notification->sentAt)) {
            return;
        }

        $result = $sendNotificationAction->handle(
            notification: $notification,
            channel: $findChannelAction->handle($notification->channelId)
        );

        if ($result->delivered) {
            return;
        }

        if ($result->permanent) {
            $this->fail($result->error);

            return;
        }

        if (!is_null($result->retryAfterSeconds)) {
            $this->release($result->retryAfterSeconds);

            return;
        }

        throw new RuntimeException($result->error ?? 'unknown error');
    }
}
