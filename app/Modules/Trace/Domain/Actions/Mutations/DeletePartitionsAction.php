<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Drops the hourly partitions of traces that ended before the moment given.
 *
 * A failure stops the run but does not lose it: the hours already dropped are counted, and
 * the failure comes back beside them.
 */
readonly class DeletePartitionsAction
{
    public function __construct(
        private TraceRepository $traceRepository,
    ) {
    }

    public function handle(Carbon $loggedAtTo): DeletedTracesObject
    {
        $partitionsCount = 0;
        $tracesCount     = 0;

        try {
            $partitions = $this->traceRepository->findEndedPartitions(
                loggedAtTo: $loggedAtTo
            );

            foreach ($partitions as $partition) {
                $this->traceRepository->dropPartition($partition->id);

                ++$partitionsCount;
                $tracesCount += $partition->rowsCount;
            }
        } catch (Throwable $exception) {
            return new DeletedTracesObject(
                partitionsCount: $partitionsCount,
                tracesCount: $tracesCount,
                exception: $exception
            );
        }

        return new DeletedTracesObject(
            partitionsCount: $partitionsCount,
            tracesCount: $tracesCount
        );
    }
}
