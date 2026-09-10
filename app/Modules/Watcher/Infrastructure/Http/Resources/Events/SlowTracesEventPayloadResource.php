<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\SlowTracesEventPayloadObject;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentEventGroupResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 *
 * The shapes behind it are here because this watcher is about traces. The types that
 * read counters have no groups field at all.
 */
class SlowTracesEventPayloadResource extends AbstractApiResource
{
    private SlowTracesEventSettingsResource $settings;
    private SlowTracesEventMeasuredResource $measured;
    /** @var WatcherIncidentEventGroupResource[] */
    #[OaListItemTypeAttribute(WatcherIncidentEventGroupResource::class)]
    private array $groups;

    public function __construct(SlowTracesEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new SlowTracesEventSettingsResource($resource->settings);
        $this->measured = new SlowTracesEventMeasuredResource($resource->measured);

        $this->groups = WatcherIncidentEventGroupResource::mapIntoMe($resource->groups);
    }
}
