<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Checkers;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventPayloadObject;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;
use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;

/**
 * The intake buffer has stopped draining.
 */
readonly class BufferOverflowChecker implements WatcherCheckerInterface
{
    public function check(WatcherObject $watcher, WatcherCheckContextObject $context): ?WatcherEventPayloadInterface
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

        return new BufferOverflowEventPayloadObject(
            settings: new BufferOverflowEventSettingsObject(threshold: $settings->threshold),
            measured: new BufferOverflowEventMeasuredObject(bufferCount: $context->bufferCount)
        );
    }
}
