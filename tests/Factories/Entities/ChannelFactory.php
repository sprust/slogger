<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Notification\Repositories\ChannelRepository;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;

readonly class ChannelFactory
{
    public function __construct(private ChannelRepository $channelRepository)
    {
    }

    /** @param array<string, mixed>|null $settings */
    public function create(
        ?string $name = null,
        ?NotificationChannelTypeEnum $type = null,
        ?bool $enabled = null,
        ?array $settings = null,
    ): int {
        return $this->channelRepository->create(
            name: $name ?? fake()->word(),
            type: ($type ?? fake()->randomElement(NotificationChannelTypeEnum::cases()))->value,
            enabled: $enabled ?? fake()->boolean(),
            settings: $settings ?? [],
        )->id;
    }
}
