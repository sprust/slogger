<?php

namespace Tests\Modules\Watcher;

use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;

/**
 * Builds watchers for the tests, so that a case says what it is about instead of naming
 * eleven constructor arguments.
 */
trait WatcherFactoryTrait
{
    private function watcher(
        WatcherTypeEnum $type,
        WatcherSettingsInterface $settings,
        ?Carbon $collectSince = null,
        ?Carbon $lastTriggeredAt = null,
        ?Carbon $lastCheckedAt = null,
        int $cooldownSeconds = 300,
        int $id = 1
    ): WatcherObject {
        $now = Carbon::now();

        return new WatcherObject(
            id: $id,
            name: 'watcher',
            type: $type,
            enabled: true,
            cooldownSeconds: $cooldownSeconds,
            settings: $settings,
            match: null,
            collectSince: $collectSince,
            lastCheckedAt: $lastCheckedAt,
            lastTriggeredAt: $lastTriggeredAt,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
