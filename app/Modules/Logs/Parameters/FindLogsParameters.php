<?php

declare(strict_types=1);

namespace App\Modules\Logs\Parameters;

readonly class FindLogsParameters
{
    public function __construct(
        public ?string $searchQuery = null,
        public ?string $level = null,
    ) {
    }
}
