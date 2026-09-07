<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class InvalidBufferGrownSettingsObject implements WatcherSettingsInterface
{
    /**
     * @param int $threshold how many new invalid documents since the last check are too many
     */
    public function __construct(
        public int $threshold = 1
    ) {
    }
}
