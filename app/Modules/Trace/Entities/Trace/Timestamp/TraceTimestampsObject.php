<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Timestamp;

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
