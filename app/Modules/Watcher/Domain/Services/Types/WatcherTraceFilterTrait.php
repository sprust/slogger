<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;

/**
 * Reading the part of the settings that says which traces a watcher is about — the same
 * three lists for every type that has one.
 */
trait WatcherTraceFilterTrait
{
    /**
     * @param array<string, mixed> $settings
     */
    protected function filterOf(array $settings): WatcherTraceFilterObject
    {
        $filter = $settings['filter'] ?? null;

        // A filter of the wrong shape is no filter, not a fatal.
        if (!is_array($filter)) {
            return new WatcherTraceFilterObject();
        }

        /** @var array<string, mixed> $filter */
        return new WatcherTraceFilterObject(
            serviceIds: array_values(ArrayValueGetter::arrayIntNull($filter, 'service_ids') ?? []),
            types: array_values(ArrayValueGetter::arrayStringNull($filter, 'types') ?? []),
            tags: array_values(ArrayValueGetter::arrayStringNull($filter, 'tags') ?? [])
        );
    }
}
