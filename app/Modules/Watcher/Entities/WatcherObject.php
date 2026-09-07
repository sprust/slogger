<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;

readonly class WatcherObject
{
    public function __construct(
        public int $id,
        public string $name,
        public WatcherTypeEnum $type,
        public bool $enabled,
        public int $cooldownSeconds,
        public WatcherSettingsInterface $settings,
        public ?WatcherMatchObject $match,
        public ?Carbon $collectSince,
        public ?Carbon $lastCheckedAt,
        public ?Carbon $lastTriggeredAt,
        public Carbon $createdAt,
        public Carbon $updatedAt
    ) {
    }
}
