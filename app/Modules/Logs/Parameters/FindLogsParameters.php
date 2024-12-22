<?php

namespace App\Modules\Logs\Parameters;

readonly class FindLogsParameters
{
    /**
     * @param string[]|null $levels
     */
    public function __construct(
        public ?string $searchQuery = null,
        public ?array $levels = null,
    ) {
    }
}
