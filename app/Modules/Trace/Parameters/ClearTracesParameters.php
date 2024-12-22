<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

use Illuminate\Support\Carbon;

class ClearTracesParameters
{
    /**
     * @param string[]|null $traceIds
     * @param string[]|null $excludedTypes
     */
    public function __construct(
        public string $collectionName,
        public ?array $traceIds = null,
        public ?Carbon $loggedAtFrom = null,
        public ?Carbon $loggedAtTo = null,
        public ?string $type = null,
        public ?array $excludedTypes = null
    ) {
    }
}
