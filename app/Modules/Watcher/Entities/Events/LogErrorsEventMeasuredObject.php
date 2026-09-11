<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Events;

readonly class LogErrorsEventMeasuredObject
{
    public function __construct(
        public int $errorCount,
        public string $since,
        public string $lastMessage
    ) {
    }
}
