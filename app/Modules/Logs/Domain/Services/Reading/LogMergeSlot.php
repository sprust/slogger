<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Entry\LogEntryObject;

class LogMergeSlot
{
    /**
     * @var list<LogEntryObject>
     */
    public array $pending = [];

    public function __construct(
        public readonly LogFileStream $stream
    ) {
    }
}
