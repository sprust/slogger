<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Formats;

readonly class LogLevelNameObject
{
    public function __construct(
        public int $level,
        public string $name
    ) {
    }
}
