<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Timestamp;

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
