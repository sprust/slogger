<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;

readonly class DeleteTraceDynamicIndexAction
{
    public function __construct(
        private TraceRepository $traceRepository,
        private TraceDynamicIndexRepository $traceDynamicIndexRepository
    ) {
    }

    public function handle(string $id): bool
    {
        $index = $this->traceDynamicIndexRepository->findOneById($id);

        if ($index?->created) {
            $this->traceRepository->deleteIndexByName(
                indexName: $index->indexName,
                collectionNames: $index->collectionNames
            );
        }

        return $this->traceDynamicIndexRepository->deleteById($id);
    }
}
