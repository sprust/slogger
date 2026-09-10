<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;

/**
 * A payload that names the shapes of trace behind it.
 *
 * The watchers that read counters have nothing to put here, so they do not implement this
 * and carry no groups field at all — an absence rather than an empty list.
 */
interface HasEventGroupsInterface extends WatcherEventPayloadInterface
{
    /** @var WatcherIncidentEventGroupObject[] */
    public array $groups { get; }
}
