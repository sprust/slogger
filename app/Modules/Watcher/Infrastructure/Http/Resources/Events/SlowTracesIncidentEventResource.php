<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;

/**
 * One time this watcher spoke.
 *
 * The payload is null for an event stored under a shape this build cannot read — a
 * document from before a number existed, which the TTL clears within a month. The event
 * keeps its place in the page either way, because a page that comes back short is read as
 * the end of the list.
 */
class SlowTracesIncidentEventResource extends AbstractApiResource
{
    private string $id;
    private string $incident_id;
    private string $occurred_at;
    private ?SlowTracesEventPayloadResource $payload;

    public function __construct(WatcherIncidentEventObject $resource)
    {
        parent::__construct($resource);

        $this->id          = $resource->id;
        $this->incident_id = $resource->incidentId;
        $this->occurred_at = $resource->occurredAt->toDateTimeString();

        $this->payload = $resource->payload instanceof SlowTracesEventPayloadObject
            ? new SlowTracesEventPayloadResource($resource->payload)
            : null;
    }
}
