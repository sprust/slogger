<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Cursor;

readonly class LogCursorPositionObject
{
    public function __construct(
        public string $fileId,
        public int $position,
        public int $headLength,
        public string $headHash
    ) {
    }
}
