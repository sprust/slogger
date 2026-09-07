<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Enums\WatcherTypeEnum;

/**
 * Settings arrive as they were sent, not as an object: their shape follows the type, and
 * turning one into the other is the job of the mapper the action reaches for. A boundary
 * object carries what came over the wire.
 */
readonly class CreateWatcherParameters
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public string $name,
        public WatcherTypeEnum $type,
        public bool $enabled,
        public int $cooldownSeconds,
        public array $settings
    ) {
    }
}
