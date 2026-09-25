<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\File;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogSourceObject
{
    public function __construct(
        public string $folder,
        public string $pattern,
        public LogTypeEnum $type
    ) {
    }
}
