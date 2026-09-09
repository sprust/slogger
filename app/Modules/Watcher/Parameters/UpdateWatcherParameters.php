<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

/**
 * The type is not here on purpose: it decides the shape of the settings, so changing it
 * would mean replacing the watcher rather than editing it.
 */
readonly class UpdateWatcherParameters
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public int $cooldownSeconds,
        public ?int $notificationChannelId,
        public array $settings
    ) {
    }
}
