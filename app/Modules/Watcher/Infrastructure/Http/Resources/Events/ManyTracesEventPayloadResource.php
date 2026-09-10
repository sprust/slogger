<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\ManyTracesEventPayloadObject;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentEventGroupResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What an event of this watcher carries, typed because the route that answers with it
 * names the watcher's type.
 *
 * The shapes behind it are here because this watcher is about traces. The types that
 * read counters have no groups field at all.
 */
class ManyTracesEventPayloadResource extends AbstractApiResource
{
    private ManyTracesEventSettingsResource $settings;
    private ManyTracesEventMeasuredResource $measured;
    /** @var WatcherIncidentEventGroupResource[] */
    #[OaListItemTypeAttribute(WatcherIncidentEventGroupResource::class)]
    private array $groups;

    public function __construct(ManyTracesEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new ManyTracesEventSettingsResource($resource->settings);
        $this->measured = new ManyTracesEventMeasuredResource($resource->measured);

        $this->groups = WatcherIncidentEventGroupResource::mapIntoMe($resource->groups);
    }
}
