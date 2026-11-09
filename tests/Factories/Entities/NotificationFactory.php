<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Enums\NotificationKindEnum;
use Illuminate\Support\Carbon;

readonly class NotificationFactory
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private ChannelFactory $channelFactory,
        private WatcherFactory $watcherFactory,
        private IncidentFactory $incidentFactory,
    ) {
    }

    public function create(
        ?int $channelId = null,
        ?int $watcherId = null,
        ?string $incidentId = null,
        ?NotificationKindEnum $kind = null,
        ?string $text = null,
        ?Carbon $createdAt = null,
    ): string {
        $channelId = $channelId ?? $this->channelFactory->create();
        $watcherId = $watcherId ?? $this->watcherFactory->create(
            notificationChannelId: $channelId,
        );

        return $this->notificationRepository->create(
            channelId: $channelId,
            watcherId: $watcherId,
            incidentId: $incidentId ?? $this->incidentFactory->create(
                watcherId: $watcherId,
            ),
            kind: $kind ?? fake()->randomElement(NotificationKindEnum::cases()),
            text: $text ?? fake()->sentence(),
            createdAt: $createdAt ?? Carbon::now(),
        )->id;
    }
}
