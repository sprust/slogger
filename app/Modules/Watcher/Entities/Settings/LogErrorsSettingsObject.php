<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

readonly class LogErrorsSettingsObject implements WatcherSettingsInterface
{
    public function __construct(
        public int $threshold = 1
    ) {
    }

    public function toArray(): array
    {
        return ['threshold' => $this->threshold];
    }
}
