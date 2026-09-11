<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Entities\WatcherObject;
use Illuminate\Support\Carbon;

readonly class WatcherCountWindow
{
    public function start(WatcherObject $watcher, Carbon $now): Carbon
    {
        $since = $watcher->lastTriggeredAt;

        if (is_null($since) || (!is_null($watcher->collectSince) && $watcher->collectSince->gt($since))) {
            $since = $watcher->collectSince;
        }

        return $since ?? $now->clone()->subSeconds($watcher->cooldownSeconds);
    }
}
