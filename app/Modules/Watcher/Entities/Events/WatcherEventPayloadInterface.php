<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/**
 * What one watcher type's event carries: the numbers it was set to, the numbers it saw,
 * and for the types that are about traces, the shapes behind them.
 *
 * One implementation per WatcherTypeEnum case, each carrying only its own fields. The
 * type is not in the stored document — it belongs to the watcher, and the route that
 * reads an incident's events is what names it.
 */
interface WatcherEventPayloadInterface
{
}
