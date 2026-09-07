<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;

/**
 * The type is not here on purpose: it decides the shape of the settings, so changing it
 * would mean replacing the watcher rather than editing it.
 */
readonly class UpdateWatcherParameters
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled,
        public int $cooldownSeconds,
        public WatcherSettingsInterface $settings
    ) {
    }
}
