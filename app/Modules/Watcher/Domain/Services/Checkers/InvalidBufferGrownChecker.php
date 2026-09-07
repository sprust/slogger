<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;

/**
 * Documents the receiver could not act on have appeared since the last look.
 */
readonly class InvalidBufferGrownChecker implements WatcherCheckerInterface
{
    public function __construct(
        private CountInvalidTraceBufferSinceAction $countInvalidSinceAction
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherTriggerObject
    {
        $settings = $watcher->settings;

        if (!$settings instanceof InvalidBufferGrownSettingsObject) {
            return null;
        }

        // The first check has no previous one to measure from. One cooldown back rather
        // than the beginning of time: a watcher switched on today should not open an
        // incident about documents that failed last week.
        $since = $watcher->lastCheckedAt ?? $context->now->clone()->subSeconds($watcher->cooldownSeconds);

        $count = $this->countInvalidSinceAction->handle($since);

        if ($count < $settings->threshold) {
            return null;
        }

        return new WatcherTriggerObject([
            'invalid_count' => $count,
            'threshold'     => $settings->threshold,
            'since'         => $since->toDateTimeString(),
        ]);
    }
}
