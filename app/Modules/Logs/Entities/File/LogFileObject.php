<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\File;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogFileObject
{
    public function __construct(
        public string $id,
        public string $path,
        public string $name,
        public string $source,
        public string $folder,
        public LogTypeEnum $type,
        public int $sizeBytes,
        public int $modifiedAtMs
    ) {
    }
}
