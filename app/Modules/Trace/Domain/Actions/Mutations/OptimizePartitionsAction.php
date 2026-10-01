<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceRepository;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;

/**
 * Merges the hourly partitions of traces that ended before the moment given into one part
 * each, so that a trace written in two steps keeps one row.
 */
readonly class OptimizePartitionsAction
{
    public function __construct(
        private TraceRepository $traceRepository,
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(Carbon $loggedAtTo): int
    {
        return $this->traceRepository->optimizePartitions(
            loggedAtTo: $loggedAtTo
        );
    }
}
