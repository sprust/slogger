<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\TracesSpikeEventPayloadObject;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentEventGroupResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 *
 * The shapes behind it are here because this watcher is about traces. The types that
 * read counters have no groups field at all.
 */
class TracesSpikeEventPayloadResource extends AbstractApiResource
{
    private TracesSpikeEventSettingsResource $settings;
    private TracesSpikeEventMeasuredResource $measured;
    /** @var WatcherIncidentEventGroupResource[] */
    #[OaListItemTypeAttribute(WatcherIncidentEventGroupResource::class)]
    private array $groups;

    public function __construct(TracesSpikeEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new TracesSpikeEventSettingsResource($resource->settings);
        $this->measured = new TracesSpikeEventMeasuredResource($resource->measured);

        $this->groups = WatcherIncidentEventGroupResource::mapIntoMe($resource->groups);
    }
}
