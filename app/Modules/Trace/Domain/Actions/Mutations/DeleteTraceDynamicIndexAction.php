<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteTraceDynamicIndexActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;

readonly class DeleteTraceDynamicIndexAction implements DeleteTraceDynamicIndexActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
    }

    public function handle(string $id): bool
    {
        $index = $this->traceDynamicIndexRepository->findOneById($id);

        if ($index->created) {
            $this->traceRepository->deleteIndexByName(
                indexName: $index->indexName,
                collectionNames: $index->collectionNames
            );
        }

        return $this->traceDynamicIndexRepository->deleteById($id);
    }
}
