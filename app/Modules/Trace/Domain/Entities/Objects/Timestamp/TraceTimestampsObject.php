<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Timestamp;

use Illuminate\Support\Carbon;

class TraceTimestampsObject
{
    /**
     * @param TraceTimestampFieldObject[] $fields
     */
    public function __construct(
        public Carbon $timestamp,
        public Carbon $timestampTo,
        public array $fields
    ) {
    }
}
