<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;

/**
 * Drops one batch of dynamic indexes whose lifetime has run out.
 *
 * The same shape as FlushDynamicIndexesAction, narrowed to what has expired: a dynamic
 * index is only worth its write cost while something still queries it.
 */
readonly class DeleteExpiredTraceDynamicIndexesAction
{
    public function __construct(
        private TraceDynamicIndexRepository $traceDynamicIndexRepository,
        private TraceRepository $traceRepository,
    ) {
    }

    /**
     * @return int how many indexes this pass deleted
     */
    public function handle(int $limit = 10): int
    {
        $indexes = $this->traceDynamicIndexRepository->find(
            limit: $limit,
            inProcess: false,
            toActualUntilAt: Carbon::now()
        );

        foreach ($indexes as $index) {
            if ($index->created) {
                $this->traceRepository->deleteIndexByName(
                    indexName: $index->indexName,
                    collectionNames: $index->collectionNames
                );
            }

            $this->traceDynamicIndexRepository->deleteById(
                id: $index->id
            );
        }

        return count($indexes);
    }
}
