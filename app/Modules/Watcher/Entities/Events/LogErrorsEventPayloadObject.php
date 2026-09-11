<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

readonly class LogErrorsEventPayloadObject implements WatcherEventPayloadInterface
{
    public function __construct(
        public LogErrorsEventSettingsObject $settings,
        public LogErrorsEventMeasuredObject $measured
    ) {
    }
}
