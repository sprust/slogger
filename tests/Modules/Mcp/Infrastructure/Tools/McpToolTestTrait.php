<?php

namespace Tests\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Illuminate\Support\Carbon;

trait McpToolTestTrait
{
    private function incident(string $id = 'inc-1', int $watcherId = 4): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: $id,
            watcherId: $watcherId,
            status: WatcherIncidentStatusEnum::Opened,
            firstEventAt: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            lastEventAt: Carbon::parse('2026-09-28 11:00:00', 'UTC'),
            eventsCount: 3,
            closedAt: null,
            closedByUserId: null
        );
    }

    /**
     * @param int[] $serviceIds
     */
    private function watcherWithServices(int $id = 4, array $serviceIds = []): WatcherObject
    {
        $now = Carbon::now();

        return new WatcherObject(
            id: $id,
            name: 'buffer',
            type: WatcherTypeEnum::BufferOverflow,
            enabled: true,
            cooldownSeconds: 300,
            notificationChannelId: null,
            notifyOnOpened: true,
            notifyOnEvent: false,
            notifyOnClosed: true,
            settings: new BufferOverflowSettingsObject(),
            match: count($serviceIds) > 0 ? new WatcherMatchObject(serviceIds: $serviceIds) : null,
            collectSince: null,
            lastCheckedAt: null,
            lastTriggeredAt: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
