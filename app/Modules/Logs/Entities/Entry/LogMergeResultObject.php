<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

use App\Modules\Logs\Entities\Cursor\LogFilePositionObject;

readonly class LogMergeResultObject
{
    /**
     * @param list<LogEntryObject>        $entries
     * @param list<LogFilePositionObject> $positions
     */
    public function __construct(
        public array $entries,
        public array $positions
    ) {
    }
}
