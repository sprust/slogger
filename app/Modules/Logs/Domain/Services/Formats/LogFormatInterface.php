<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Entities\Entry\LogEntryDetailsObject;
use App\Modules\Logs\Entities\Formats\LogLevelNameObject;
use App\Modules\Logs\Entities\Index\LogEntryStartObject;

interface LogFormatInterface
{
    /**
     * @return list<LogEntryStartObject>
     */
    public function findEntryStarts(string $chunk): array;

    public function parseEntry(string $text): LogEntryDetailsObject;

    /**
     * @return list<LogLevelNameObject>
     */
    public function getLevelNames(): array;
}
