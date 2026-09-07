<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherObject;

/**
 * A watcher without its settings.
 *
 * The settings are not missing by oversight: their shape follows the type, and a list
 * holds watchers of every type at once. They are read one watcher at a time, from that
 * type's own endpoint, where the answer can be typed — see the per-type settings
 * resources.
 */
class WatcherResource extends AbstractApiResource
{
    private int $id;
    private string $name;
    private string $type;
    private bool $enabled;
    private int $cooldown_seconds;
    private ?string $collect_since;
    private ?string $last_checked_at;
    private ?string $last_triggered_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(WatcherObject $resource)
    {
        parent::__construct($resource);

        $this->id                = $resource->id;
        $this->name              = $resource->name;
        $this->type              = $resource->type->value;
        $this->enabled           = $resource->enabled;
        $this->cooldown_seconds  = $resource->cooldownSeconds;
        $this->collect_since     = $resource->collectSince?->toDateTimeString();
        $this->last_checked_at   = $resource->lastCheckedAt?->toDateTimeString();
        $this->last_triggered_at = $resource->lastTriggeredAt?->toDateTimeString();
        $this->created_at        = $resource->createdAt->toDateTimeString();
        $this->updated_at        = $resource->updatedAt->toDateTimeString();
    }
}
