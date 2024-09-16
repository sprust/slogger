<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\DeleteTraceDynamicIndexActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

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
                name: $index->name
            );
        }

        return $this->traceDynamicIndexRepository->deleteById($id);
    }
}
