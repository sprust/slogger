<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Entities\Settings\HasTraceFilterInterface;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherMatchObject;

/**
 * The `watchers.trace_match` column, written from a watcher's settings.
 *
 * This is the entire contract between the panel and the Go receiver, which is why the
 * shape lives in one class: what is written here is parsed there, and the two have to be
 * read side by side.
 */
readonly class WatcherMatchFactory
{
    /**
     * Null for a watcher the receiver has no business loading — the buffer ones, whose
     * settings describe no traces at all.
     */
    public function make(WatcherSettingsInterface $settings): ?WatcherMatchObject
    {
        if (!$settings instanceof HasTraceFilterInterface) {
            return null;
        }

        $filter = $settings->traceFilter();

        return new WatcherMatchObject(
            serviceIds: $filter->serviceIds,
            types: $filter->types,
            tags: $filter->tags
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(?WatcherMatchObject $match): ?array
    {
        if (is_null($match)) {
            return null;
        }

        return [
            'v'           => $match->version,
            'service_ids' => $match->serviceIds,
            'types'       => $match->types,
            'tags'        => $match->tags,
        ];
    }
}
