<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Cursor;

use App\Modules\Logs\Enums\LogCursorDirectionEnum;

readonly class LogCursorObject
{
    /**
     * @param list<LogCursorPositionObject> $positions
     */
    public function __construct(
        public LogCursorDirectionEnum $direction,
        public array $positions
    ) {
    }
}
