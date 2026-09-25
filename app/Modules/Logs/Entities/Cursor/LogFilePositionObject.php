<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Cursor;

readonly class LogFilePositionObject
{
    public function __construct(
        public string $fileId,
        public int $position
    ) {
    }
}
