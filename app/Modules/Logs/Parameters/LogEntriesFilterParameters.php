<?php

declare(strict_types=1);

namespace App\Modules\Logs\Parameters;

readonly class LogEntriesFilterParameters
{
    /**
     * @param list<int>|null $levels
     */
    public function __construct(
        public ?array $levels = null,
        public ?int $fromTime = null,
        public ?int $toTime = null,
        public ?string $searchQuery = null
    ) {
    }
}
