<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;

readonly class FindTraceDynamicIndexAction
{
    public function __construct(
        private TraceFieldTitlesService $traceFieldTitlesService,
        private TraceDynamicIndexRepository $traceDynamicIndexRepository
    ) {
    }

    public function handle(string $indexId): ?TraceDynamicIndexObject
    {
        $index = $this->traceDynamicIndexRepository->findOneById(
            id: $indexId,
        );

        if ($index === null) {
            return null;
        }

        return new TraceDynamicIndexObject(
            id: $index->id,
            name: $index->name,
            indexName: $index->indexName,
            collectionNames: $index->collectionNames,
            fields: array_map(
                fn(TraceDynamicIndexFieldDto $dtoField) => new TraceDynamicIndexFieldObject(
                    name: $dtoField->fieldName,
                    title: $this->traceFieldTitlesService->get($dtoField->fieldName),
                ),
                $index->fields
            ),
            inProcess: $index->inProcess,
            created: $index->created,
            error: $index->error,
            actualUntilAt: $index->actualUntilAt,
            createdAt: $index->createdAt,
        );
    }
}
