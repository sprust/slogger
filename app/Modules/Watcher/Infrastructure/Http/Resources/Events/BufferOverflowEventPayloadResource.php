<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 */
class BufferOverflowEventPayloadResource extends AbstractApiResource
{
    private BufferOverflowEventSettingsResource $settings;
    private BufferOverflowEventMeasuredResource $measured;

    public function __construct(BufferOverflowEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new BufferOverflowEventSettingsResource($resource->settings);
        $this->measured = new BufferOverflowEventMeasuredResource($resource->measured);
    }
}
