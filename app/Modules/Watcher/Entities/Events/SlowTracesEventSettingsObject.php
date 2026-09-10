<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

/** What the watcher was set to when it went off. */
readonly class SlowTracesEventSettingsObject
{
    public function __construct(
        public float $duration,
        public int $windowMinutes
    ) {
    }
}
