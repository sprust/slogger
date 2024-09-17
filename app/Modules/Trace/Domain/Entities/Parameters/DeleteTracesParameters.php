<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use Illuminate\Support\Carbon;

class DeleteTracesParameters
{
    public function __construct(
        public ?Carbon $loggedAtFrom = null,
        public ?Carbon $loggedAtTo = null,
        public ?string $type = null,
        public ?array $excludedTypes = null
    ) {
    }
}
