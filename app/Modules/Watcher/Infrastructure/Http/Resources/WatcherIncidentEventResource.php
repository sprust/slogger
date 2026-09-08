<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;

class WatcherIncidentEventResource extends AbstractApiResource
{
    private string $id;
    private string $incident_id;
    private string $occurred_at;
    private WatcherIncidentEventPayloadResource $payload;

    public function __construct(WatcherIncidentEventObject $resource)
    {
        parent::__construct($resource);

        $this->id          = $resource->id;
        $this->incident_id = $resource->incidentId;
        $this->occurred_at = $resource->occurredAt->toDateTimeString();
        $this->payload     = new WatcherIncidentEventPayloadResource($resource->payload);
    }
}
