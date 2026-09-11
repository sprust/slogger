<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Log;

readonly class LogLevelStatObject
{
    public function __construct(
        public int $count,
        public ?string $lastMessage
    ) {
    }
}
