<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Requests;

use App\Modules\Watcher\Entities\WatcherTimelineObject;

/**
 * What every watcher has, whatever it watches.
 *
 * The type is not among them: it is in the route. A watcher of one type carrying another
 * type's settings is a state this API cannot express, which is the whole point of a
 * request per type.
 */
trait WatcherRulesTrait
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function commonRules(): array
    {
        return [
            'name'    => ['required', 'string', 'min:1', 'max:255'],
            'enabled' => ['required', 'boolean'],
            // A day at the top: a watcher that may speak less often than that is one
            // nobody will hear from about a problem that started this morning.
            'cooldown_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
        ];
    }

    /**
     * A stretch of time the watcher looks at, in minutes.
     *
     * Capped at how far a line reaches: a window longer than that reads buckets that were
     * never kept, and every checker would take the part it cannot see for an absence.
     *
     * @return array<int, string>
     */
    protected function timelineMinutesRules(): array
    {
        return ['required', 'integer', 'min:1', 'max:' . WatcherTimelineObject::MAX_DEPTH_MINUTES];
    }

    /**
     * Which traces the watcher is about.
     *
     * Optional, all of it: a filter nobody set means every trace, which is a sensible
     * default and an explicit one. The numbers above are required instead — a threshold
     * quietly falling back to a default is a watcher measuring something nobody asked
     * for.
     *
     * @return array<string, array<int, string>>
     */
    protected function traceFilterRules(): array
    {
        return [
            'settings.filter'               => ['sometimes', 'array'],
            'settings.filter.service_ids'   => ['sometimes', 'array'],
            'settings.filter.service_ids.*' => ['integer'],
            'settings.filter.types'         => ['sometimes', 'array'],
            'settings.filter.types.*'       => ['string', 'min:1', 'max:255'],
            'settings.filter.tags'          => ['sometimes', 'array'],
            'settings.filter.tags.*'        => ['string', 'min:1', 'max:255'],
        ];
    }
}
