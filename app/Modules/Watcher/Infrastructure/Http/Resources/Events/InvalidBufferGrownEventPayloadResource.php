<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventPayloadObject;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 */
class InvalidBufferGrownEventPayloadResource extends AbstractApiResource
{
    private InvalidBufferGrownEventSettingsResource $settings;
    private InvalidBufferGrownEventMeasuredResource $measured;

    public function __construct(InvalidBufferGrownEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new InvalidBufferGrownEventSettingsResource($resource->settings);
        $this->measured = new InvalidBufferGrownEventMeasuredResource($resource->measured);
    }
}
