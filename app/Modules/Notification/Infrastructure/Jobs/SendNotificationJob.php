<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Jobs;

use App\Modules\Notification\Domain\UseCases\SendNotificationUseCase;
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
    public array $backoff = [
        15,
        60,
        300,
        900,
        3600,
    ];

    public function __construct(private readonly string $notificationId)
    {
    }

    public function handle(
        SendNotificationUseCase $sendNotificationUseCase
    ): void {
        $result = $sendNotificationUseCase->handle($this->notificationId);

        if ($result === null) {
            return;
        }

        if ($result->delivered) {
            return;
        }

        if ($result->permanent) {
            $this->fail(
                exception: $result->error,
            );

            return;
        }

        if (!is_null($result->retryAfterSeconds)) {
            $this->release(
                delay: $result->retryAfterSeconds,
            );

            return;
        }

        throw new RuntimeException(
            message: $result->error ?? 'unknown error',
        );
    }
}
