<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

class DeleteTracesParameters
{
    /**
     * @param string[]|null $traceIds
     * @param string[]|null $excludedTypes
     */
    public function __construct(
        public ?array $traceIds = null,
        public ?Carbon $loggedAtFrom = null,
        public ?Carbon $loggedAtTo = null,
        public ?string $type = null,
        public ?array $excludedTypes = null
    ) {
    }
}
