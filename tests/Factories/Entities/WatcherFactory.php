<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;

readonly class WatcherFactory
{
    public function __construct(private WatcherRepository $watcherRepository, private ChannelFactory $channelFactory)
    {
    }

    /**
     * @param array<string, mixed>|null $settings
     * @param array<string, mixed>|null $traceMatch
     */
    public function create(
        ?string $name = null,
        ?WatcherTypeEnum $type = null,
        ?bool $enabled = null,
        ?int $cooldownSeconds = null,
        ?int $notificationChannelId = null,
        ?bool $notifyOnOpened = null,
        ?bool $notifyOnEvent = null,
        ?bool $notifyOnClosed = null,
        ?array $settings = null,
        ?array $traceMatch = null,
        ?Carbon $collectSince = null,
    ): int {
        $type = $type ?? fake()->randomElement(WatcherTypeEnum::cases());

        return $this->watcherRepository->create(
            name: $name ?? fake()->word(),
            type: $type->value,
            enabled: $enabled ?? fake()->boolean(),
            cooldownSeconds: $cooldownSeconds ?? $type->defaultCooldownSeconds(),
            notificationChannelId: $notificationChannelId ?? $this->channelFactory->create(),
            notifyOnOpened: $notifyOnOpened ?? fake()->boolean(),
            notifyOnEvent: $notifyOnEvent ?? fake()->boolean(),
            notifyOnClosed: $notifyOnClosed ?? fake()->boolean(),
            settings: $settings ?? [],
            traceMatch: $traceMatch ?? [],
            collectSince: $collectSince ?? Carbon::now(),
        )->id;
    }
}
