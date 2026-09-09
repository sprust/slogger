<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories\Dto;

use Illuminate\Support\Carbon;

/**
 * A watcher row, as it is stored.
 *
 * The type is a string and the settings are the json that was written: reading them as
 * something meaningful takes knowing what each type is, and that knowledge belongs to the
 * domain. The repository's job ends at handing over what the row says.
 */
readonly class WatcherDto
{
    /**
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $traceMatch
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public bool $enabled,
        public int $cooldownSeconds,
        public ?int $notificationChannelId,
        public array $settings,
        public ?array $traceMatch,
        public ?Carbon $collectSince,
        public ?Carbon $lastCheckedAt,
        public ?Carbon $lastTriggeredAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
