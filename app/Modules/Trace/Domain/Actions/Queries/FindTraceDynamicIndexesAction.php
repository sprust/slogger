<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexDto;
use App\Modules\Trace\Transports\TraceDynamicIndexTransport;

readonly class FindTraceDynamicIndexesAction implements FindTraceDynamicIndexesActionInterface
{
    private int $limit;

    public function __construct(
        private TraceDynamicIndexTransport $traceDynamicIndexTransport,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
        $this->limit = 50;
    }

    public function handle(): array
    {
        $tracesDto = $this->traceDynamicIndexRepository->find(
            limit: $this->limit,
            orderByCreatedAtDesc: true
        );

        return array_map(
            fn(TraceDynamicIndexDto $dto) => $this->traceDynamicIndexTransport->fromDto($dto),
            $tracesDto
        );
    }
}
