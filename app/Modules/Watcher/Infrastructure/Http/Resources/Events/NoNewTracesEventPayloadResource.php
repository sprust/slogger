<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventPayloadObject;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 */
class NoNewTracesEventPayloadResource extends AbstractApiResource
{
    private NoNewTracesEventSettingsResource $settings;
    private NoNewTracesEventMeasuredResource $measured;

    public function __construct(NoNewTracesEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new NoNewTracesEventSettingsResource($resource->settings);
        $this->measured = new NoNewTracesEventMeasuredResource($resource->measured);
    }
}
