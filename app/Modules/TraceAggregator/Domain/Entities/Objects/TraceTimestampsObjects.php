<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

class TraceTimestampsObjects
{
    /**
     * @param TraceTimestampsObject[] $items
     */
    public function __construct(
        public Carbon $loggedAtFrom,
        public array $items,
    ) {
    }
}
