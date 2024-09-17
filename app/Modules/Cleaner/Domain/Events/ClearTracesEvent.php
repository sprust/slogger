<?php

namespace App\Modules\Cleaner\Domain\Events;

use Illuminate\Support\Carbon;

readonly class ClearTracesEvent
{
    /**
     * @param string[] $excludedTypes
     */
    public function __construct(
        public Carbon $loggedAtFrom,
        public string $type,
        public array $excludedTypes,
    ) {
    }
}
