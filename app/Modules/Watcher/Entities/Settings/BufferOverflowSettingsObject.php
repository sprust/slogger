<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class BufferOverflowSettingsObject implements WatcherSettingsInterface
{
    /**
     * @param int $threshold how many documents in the buffer are too many
     */
    public function __construct(
        public int $threshold = 1000
    ) {
    }

    public function toArray(): array
    {
        return ['threshold' => $this->threshold];
    }
}
