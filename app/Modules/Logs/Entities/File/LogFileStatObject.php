<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\File;

readonly class LogFileStatObject
{
    public function __construct(
        public bool $exists,
        public int $sizeBytes,
        public int $modifiedAtMs
    ) {
    }
}
