<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Trace\Domain\Actions\Queries\CountInvalidTraceBufferSinceAction;
use App\Modules\Watcher\Domain\Services\WatcherCountWindow;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventPayloadObject;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;

/**
 * Documents the receiver could not act on have appeared since the watcher last spoke.
 */
readonly class InvalidBufferGrownChecker implements WatcherCheckerInterface
{
    public function __construct(
        private CountInvalidTraceBufferSinceAction $countInvalidSinceAction,
        private WatcherCountWindow $countWindow
    ) {
    }

    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherEventPayloadInterface
    {
        $settings = $watcher->settings;

        if (!$settings instanceof InvalidBufferGrownSettingsObject) {
            return null;
        }

        $since = $this->countWindow->start($watcher, $context->now);

        $count = $this->countInvalidSinceAction->handle($since, $context->now);

        if ($count < $settings->threshold) {
            return null;
        }

        return new InvalidBufferGrownEventPayloadObject(
            settings: new InvalidBufferGrownEventSettingsObject(threshold: $settings->threshold),
            measured: new InvalidBufferGrownEventMeasuredObject(
                invalidCount: $count,
                since: $since->toDateTimeString()
            )
        );
    }
}
