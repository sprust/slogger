<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

use App\Modules\Logs\Entities\Index\LogIndexRecordObject;

readonly class LogTextRangeObject
{
    /**
     * @param list<LogIndexRecordObject> $records
     */
    public function __construct(
        public int $offset,
        public int $length,
        public array $records
    ) {
    }
}
