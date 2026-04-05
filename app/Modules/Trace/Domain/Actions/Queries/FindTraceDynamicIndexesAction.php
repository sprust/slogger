<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;

readonly class FindTraceDynamicIndexesAction
{
    private int $limit;

    public function __construct(
        private TraceFieldTitlesService $traceFieldTitlesService,
        private TraceDynamicIndexRepository $traceDynamicIndexRepository
    ) {
        $this->limit = 100;
    }

    /**
     * @return TraceDynamicIndexObject[]
     */
    public function handle(): array
    {
        $indexes = $this->traceDynamicIndexRepository->find(
            limit: $this->limit,
            orderByCreatedAtDesc: true
        );

        return array_map(
            fn(TraceDynamicIndexDto $dto) => new TraceDynamicIndexObject(
                id: $dto->id,
                name: $dto->name,
                indexName: $dto->indexName,
                collectionNames: $dto->collectionNames,
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
            $indexes
        );
    }
}
