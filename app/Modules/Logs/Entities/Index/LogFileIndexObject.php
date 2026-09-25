<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

use App\Modules\Logs\Entities\File\LogFileObject;

readonly class LogFileIndexObject
{
    public function __construct(
        public LogFileObject $file,
        public LogIndexMetaObject $meta
    ) {
    }
}
