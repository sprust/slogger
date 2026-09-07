<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Parameters;

use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class CreateWatcherParameters
{
    public function __construct(
        public string $name,
        public WatcherTypeEnum $type,
        public bool $enabled,
        public int $cooldownSeconds,
        public WatcherSettingsInterface $settings
    ) {
    }
}
