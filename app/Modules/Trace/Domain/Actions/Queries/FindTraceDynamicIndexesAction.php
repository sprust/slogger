<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;

readonly class FindTraceDynamicIndexesAction implements FindTraceDynamicIndexesActionInterface
{
    private int $limit;

    public function __construct(
        private TraceFieldTitlesService $traceFieldTitlesService,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
        $this->limit = 50;
    }

    public function handle(): array
    {
        $traces = $this->traceDynamicIndexRepository->find(
            limit: $this->limit,
            orderByCreatedAtDesc: true
        );

        return array_map(
            fn(TraceDynamicIndexDto $dto) => new TraceDynamicIndexObject(
                id: $dto->id,
                name: $dto->name,
                fields: array_map(
                    fn(TraceDynamicIndexFieldDto $dtoField) => new TraceDynamicIndexFieldObject(
                        name: $dtoField->fieldName,
                        title: $this->traceFieldTitlesService->get($dtoField->fieldName),
                    ),
                    $dto->fields
                ),
                inProcess: $dto->inProcess,
                created: $dto->created,
                error: $dto->error,
                actualUntilAt: $dto->actualUntilAt,
                createdAt: $dto->createdAt,
            ),
            $traces
        );
    }
}
