<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Index;

readonly class LogLevelCountObject
{
    public function __construct(
        public int $level,
        public int $count
    ) {
    }
}
