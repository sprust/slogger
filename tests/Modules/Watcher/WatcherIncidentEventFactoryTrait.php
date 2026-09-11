<?php

namespace Tests\Modules\Watcher;

use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use Illuminate\Support\Carbon;

/**
 * Builds incident events for the tests, so that a case says which payload it is about
 * instead of naming four constructor arguments.
 */
trait WatcherIncidentEventFactoryTrait
{
    private function incidentEvent(?WatcherEventPayloadInterface $payload = null): WatcherIncidentEventObject
    {
        return new WatcherIncidentEventObject(
            id: '68be1f000000000000000010',
            incidentId: '68be1f000000000000000009',
            payload: $payload ?? $this->bufferOverflowPayload(),
            occurredAt: Carbon::parse('2026-09-08 19:20:03')
        );
    }

    /** The simplest payload there is: one number set, one number seen. */
    private function bufferOverflowPayload(
        int $threshold = 1000,
        int $bufferCount = 12000
    ): BufferOverflowEventPayloadObject {
        return new BufferOverflowEventPayloadObject(
            settings: new BufferOverflowEventSettingsObject(threshold: $threshold),
            measured: new BufferOverflowEventMeasuredObject(bufferCount: $bufferCount)
        );
    }

    /**
     * @param string[] $tags
     */
    private function eventGroup(
        string $type,
        array $tags = [],
        int $count = 1,
        int $serviceId = 1,
        ?float $durationMax = null,
        ?string $slowestTraceId = null,
        ?string $slowestTraceLoggedAt = null
    ): WatcherIncidentEventGroupObject {
        return new WatcherIncidentEventGroupObject(
            serviceId: $serviceId,
            type: $type,
            tags: $tags,
            count: $count,
            durationMax: $durationMax,
            slowestTraceId: $slowestTraceId,
            slowestTraceLoggedAt: $slowestTraceLoggedAt
        );
    }
}
