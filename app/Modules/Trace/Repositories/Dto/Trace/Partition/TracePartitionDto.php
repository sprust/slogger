<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Partition;

/**
 * An hourly partition of the traces table.
 */
readonly class TracePartitionDto
{
    public function __construct(
        // hex digits, checked before it is returned
        public string $id,
        public int $rowsCount,
    ) {
    }
}
