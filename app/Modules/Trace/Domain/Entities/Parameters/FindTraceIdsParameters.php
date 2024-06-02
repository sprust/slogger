<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

class FindTraceIdsParameters
{
    public function __construct(
        public int $limit,
        public ?Carbon $loggedAtTo = null,
        public ?string $type = null,
        public ?array $excludedTypes = null
    ) {
    }
}
