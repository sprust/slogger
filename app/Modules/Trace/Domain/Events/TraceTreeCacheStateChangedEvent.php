<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Events;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;

/**
 * The state of one tree build moved: it finished, it failed, it was canceled, or it got
 * further along.
 *
 * Carries the whole state object rather than the fields that changed. Whoever listens is
 * showing a state, not applying a delta, and the object is already assembled wherever
 * this is raised.
 */
readonly class TraceTreeCacheStateChangedEvent
{
    public function __construct(
        public TraceTreeCacheStateObject $state,
    ) {
    }
}
