<?php

namespace App\Models\Watchers;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * One time a watcher said it again, under the incident it belongs to.
 *
 * Retired by a TTL on `occurredAt`: a month from when it happened, on its own clock. The
 * incident above it is retired a month after it was closed, so an incident left open for
 * longer keeps its row and its count while its earliest events go.
 *
 * @property string               $_id
 * @property string               $incidentId
 * @property Carbon               $occurredAt
 * @property array<string, mixed> $payload
 */
class WatcherIncidentEvent extends AbstractTraceModel
{
    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    public function getCollectionName(): string
    {
        return 'watcherIncidentEvents';
    }
}
