<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherIncidentObject;

class WatcherIncidentResource extends AbstractApiResource
{
    private string $id;
    private int $watcher_id;
    private string $status;
    private string $first_event_at;
    private string $last_event_at;
    private int $events_count;
    private ?string $closed_at;
    private ?int $closed_by_user_id;

    public function __construct(WatcherIncidentObject $resource)
    {
        parent::__construct($resource);

        $this->id                = $resource->id;
        $this->watcher_id        = $resource->watcherId;
        $this->status            = $resource->status->value;
        $this->first_event_at    = $resource->firstEventAt->toDateTimeString();
        $this->last_event_at     = $resource->lastEventAt->toDateTimeString();
        $this->events_count      = $resource->eventsCount;
        $this->closed_at         = $resource->closedAt?->toDateTimeString();
        $this->closed_by_user_id = $resource->closedByUserId;
    }
}
