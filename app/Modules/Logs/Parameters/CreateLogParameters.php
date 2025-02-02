<?php

declare(strict_types=1);

namespace App\Modules\Logs\Parameters;

use Illuminate\Support\Carbon;

readonly class CreateLogParameters
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
