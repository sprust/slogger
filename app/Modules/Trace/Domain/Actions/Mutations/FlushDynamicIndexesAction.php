<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\FlushDynamicIndexesActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

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
                        name: $index->name
                    );
                }

                $this->traceDynamicIndexRepository->deleteByName(
                    name: $index->name
                );
            }
        }
    }
}
