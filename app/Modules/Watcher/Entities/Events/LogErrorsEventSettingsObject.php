<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

readonly class LogErrorsEventSettingsObject
{
    public function __construct(
        public int $threshold
    ) {
    }
}
