<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

use Illuminate\Support\Carbon;

readonly class FindMcpLogEntriesParameters
{
    /**
     * @param string[] $levels
     */
    public function __construct(
        public string $source,
        public array $levels,
        public ?Carbon $from,
        public ?Carbon $to,
        public ?string $query,
        public int $limit
    ) {
    }
}
