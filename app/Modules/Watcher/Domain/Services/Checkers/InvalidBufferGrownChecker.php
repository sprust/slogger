<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use Illuminate\Support\Carbon;

/**
 * Documents the receiver could not act on have appeared since the watcher last spoke.
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

        $since = $this->windowStart($watcher, $context->now);

        $count = $this->countInvalidSinceAction->handle($since, $context->now);

        if ($count < $settings->threshold) {
            return null;
        }

        return new WatcherTriggerObject(
            settings: ['threshold' => $settings->threshold],
            measured: [
                'invalid_count' => $count,
                'since'         => $since->toDateTimeString(),
            ]
        );
    }

    /**
     * The moment this count starts from.
     *
     * When the watcher last spoke, not when it last looked. Every check used to move the
     * lower bound, including the checks whose trigger the cooldown then threw away — so
     * with a ten-minute cooldown and three failures a minute, the event that finally got
     * through reported three, and the thirty that failed in between were reported by
     * nothing at all.
     *
     * Never earlier than the watcher started collecting, so one switched off and on again
     * does not open an incident about what failed while it was off. And with neither —
     * the first check of a watcher that has never spoken — one cooldown back, which is as
     * far as it could have reported anyway.
     */
    private function windowStart(WatcherObject $watcher, Carbon $now): Carbon
    {
        $since = $watcher->lastTriggeredAt;

        if (is_null($since) || (!is_null($watcher->collectSince) && $watcher->collectSince->gt($since))) {
            $since = $watcher->collectSince;
        }

        return $since ?? $now->clone()->subSeconds($watcher->cooldownSeconds);
    }
}
