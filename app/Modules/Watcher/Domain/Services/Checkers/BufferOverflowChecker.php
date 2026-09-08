<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;

/**
 * The intake buffer has stopped draining.
 */
readonly class BufferOverflowChecker implements WatcherCheckerInterface
{
    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherTriggerObject
    {
        $settings = $watcher->settings;

        if (!$settings instanceof BufferOverflowSettingsObject) {
            return null;
        }

        // Unknown, not empty. Reporting a healthy buffer because Mongo would not answer is
        // the one wrong answer here.
        if (is_null($context->bufferCount)) {
            return null;
        }

        if ($context->bufferCount < $settings->threshold) {
            return null;
        }

        return new WatcherTriggerObject(
            settings: ['threshold' => $settings->threshold],
            measured: ['buffer_count' => $context->bufferCount]
        );
    }
}
