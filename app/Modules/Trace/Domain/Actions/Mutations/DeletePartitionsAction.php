<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;

/**
 * Drops the hourly partitions of traces that ended before the moment given.
 */
readonly class DeletePartitionsAction
{
    public function __construct(
        private TraceRepository $traceRepository,
    ) {
    }

    public function handle(Carbon $loggedAtTo): DeletedTracesObject
    {
        return $this->traceRepository->deletePartitions(
            loggedAtTo: $loggedAtTo
        );
    }
}
