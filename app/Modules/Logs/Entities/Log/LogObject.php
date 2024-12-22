<?php

namespace App\Modules\Logs\Entities\Log;

use Illuminate\Support\Carbon;

readonly class LogObject
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $level,
        public string $message,
        public array $context,
        public string $channel,
        public Carbon $loggedAt
    ) {
    }
}
