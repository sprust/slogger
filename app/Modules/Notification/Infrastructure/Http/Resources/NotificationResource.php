<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\NotificationObject;

class NotificationResource extends AbstractApiResource
{
    private string $id;
    private ?int $watcher_id;
    private ?string $incident_id;
    private string $kind;
    private string $text;
    private ?string $sent_at;
    private ?string $error;
    private string $created_at;

    public function __construct(NotificationObject $resource)
    {
        parent::__construct($resource);

        $this->id          = $resource->id;
        $this->watcher_id  = $resource->watcherId;
        $this->incident_id = $resource->incidentId;
        $this->kind        = $resource->kind->value;
        $this->text        = $resource->text;
        $this->sent_at     = $resource->sentAt?->toDateTimeString();
        $this->error       = $resource->error;
        $this->created_at  = $resource->createdAt->toDateTimeString();
    }
}
