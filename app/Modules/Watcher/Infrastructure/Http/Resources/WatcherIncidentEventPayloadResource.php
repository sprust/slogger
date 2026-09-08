<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What a watcher saw, split into what it was told to look for and what it found.
 *
 * Loose here and strict on the writing side is not an inconsistency. A request that
 * accepts a field it should not silently changes what a watcher measures; a response that
 * carries a null nobody reads changes nothing.
 */
class WatcherIncidentEventPayloadResource extends AbstractApiResource
{
    private WatcherIncidentEventSettingsResource $settings;
    private WatcherIncidentEventMeasuredResource $measured;
    /** @var WatcherIncidentEventGroupResource[] */
    #[OaListItemTypeAttribute(WatcherIncidentEventGroupResource::class)]
    private array $groups;

    public function __construct(WatcherIncidentEventObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new WatcherIncidentEventSettingsResource($resource->settings);
        $this->measured = new WatcherIncidentEventMeasuredResource($resource->measured);
        $this->groups   = $this->groupsOf($resource->groups);
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return WatcherIncidentEventGroupResource[]
     */
    private function groupsOf(array $groups): array
    {
        $resources = [];

        foreach ($groups as $group) {
            $resources[] = new WatcherIncidentEventGroupResource($group);
        }

        return $resources;
    }
}
