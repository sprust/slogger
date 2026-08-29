<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Throwable;

/**
 * Builds one batch of the dynamic indexes that were requested and are still pending.
 *
 * One pass, not a loop: the schedule belongs to whoever calls this. An index that fails
 * to build is recorded as failed rather than retried here, which is what keeps a broken
 * index from holding up the batch behind it.
 */
readonly class BuildPendingTraceDynamicIndexesAction
{
    public function __construct(
        private TraceDynamicIndexRepository $traceDynamicIndexRepository,
        private TraceRepository $traceRepository,
    ) {
    }

    /**
     * @return int how many indexes this pass handled
     */
    public function handle(int $limit = 10): int
    {
        $indexes = $this->traceDynamicIndexRepository->find(
            limit: $limit,
            inProcess: true,
        );

        foreach ($indexes as $index) {
            $exception = null;

            try {
                $indexCreated = $this->traceRepository->createIndex(
                    name: $index->indexName,
                    collectionNames: $index->collectionNames,
                    fields: $index->fields
                );
            } catch (Throwable $exception) {
                $indexCreated = false;
            }

            // Always written, whichever way the build went: an index left with
            // inProcess true is picked up again on every later pass, for ever.
            $this->traceDynamicIndexRepository->updateByName(
                name: $index->name,
                inProcess: false,
                created: $indexCreated,
                exception: $exception
            );
        }

        return count($indexes);
    }
}
