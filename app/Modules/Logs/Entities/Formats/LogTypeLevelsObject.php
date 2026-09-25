<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Formats;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogTypeLevelsObject
{
    /**
     * @param list<int> $levels
     */
    public function __construct(
        public LogTypeEnum $type,
        public array $levels
    ) {
    }
}
