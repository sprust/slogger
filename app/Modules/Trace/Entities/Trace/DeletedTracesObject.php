<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

use Throwable;

readonly class DeletedTracesObject
{
    public function __construct(
        public int $partitionsCount,
        public int $tracesCount,
        // the failure that stopped the run, the counts above are what was dropped before it
        public ?Throwable $exception = null,
    ) {
    }
}
