<?php

namespace App\Models\Watchers;

use App\Models\AbstractTraceModel;
use Illuminate\Support\Carbon;

/**
 * One time a watcher said it again, under the incident it belongs to.
 *
 * Retired by a TTL on `occurredAt`, like the incident above it. The two are given the same
 * lifetime, so an incident and the history behind it go together rather than leaving a row
 * whose events have gone.
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
