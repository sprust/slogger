<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

/**
 * Settings that describe which traces the watcher is about.
 *
 * This interface is what decides whether a watcher reaches the receiver at all: the
 * filter behind it becomes the `trace_match` column, and a watcher without one — the
 * buffer watchers — is never loaded there.
 */
interface HasTraceFilterInterface extends WatcherSettingsInterface
{
    public function traceFilter(): WatcherTraceFilterObject;
}
