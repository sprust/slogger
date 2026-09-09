<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;

/**
 * Decides whether one watcher has something to say right now.
 *
 * Null means it has not: not an error, not "nothing was collected" — simply nothing worth
 * an event. Whether the answer is turned into an incident, and whether it is too soon to
 * speak again, is not decided here.
 */
interface WatcherCheckerInterface
{
    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherTriggerObject;
}
