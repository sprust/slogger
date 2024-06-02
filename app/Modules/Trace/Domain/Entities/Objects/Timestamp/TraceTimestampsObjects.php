<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

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
