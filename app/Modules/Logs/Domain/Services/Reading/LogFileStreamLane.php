<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Index\LogIndexRecordObject;

class LogFileStreamLane
{
    /**
     * @var list<LogIndexRecordObject>
     */
    public array $buffer = [];

    public function __construct(
        public readonly ?int $level,
        public readonly int $low,
        public readonly int $high,
        public int $next
    ) {
    }
}
