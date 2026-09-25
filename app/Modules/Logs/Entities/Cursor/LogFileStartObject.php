<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Cursor;

use App\Modules\Logs\Entities\Index\LogFileIndexObject;

readonly class LogFileStartObject
{
    public function __construct(
        public LogFileIndexObject $fileIndex,
        public ?int $position,
        public bool $restarted
    ) {
    }
}
