<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\FlushDynamicIndexesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;

readonly class FlushDynamicIndexesAction implements FlushDynamicIndexesActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
    }

    public function handle(): void
    {
        $page = 0;

        while (true) {
            ++$page;

            $indexes = $this->traceDynamicIndexRepository->find(
                limit: 20,
                inProcess: false,
                sortByCreatedAtAsc: true
            );

            if (empty($indexes)) {
                break;
            }

            foreach ($indexes as $index) {
                if ($index->created) {
                    $this->traceRepository->deleteIndexByName(
                        name: $index->indexName
                    );
                }

                $this->traceDynamicIndexRepository->deleteById(
                    id: $index->id
                );
            }
        }
    }
}
