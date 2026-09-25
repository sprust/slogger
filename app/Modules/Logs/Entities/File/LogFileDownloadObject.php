<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\File;

readonly class LogFileDownloadObject
{
    /**
     * @param iterable<string> $chunks
     */
    public function __construct(
        public LogFileObject $file,
        public iterable $chunks
    ) {
    }
}
