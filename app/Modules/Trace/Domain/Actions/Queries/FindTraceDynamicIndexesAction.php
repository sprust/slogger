<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceDynamicIndexesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use Illuminate\Support\Arr;

readonly class FindTraceDynamicIndexesAction implements FindTraceDynamicIndexesActionInterface
{
    private int $limit;

    public function __construct(
        private TraceFieldTitlesService $traceFieldTitlesService,
        private FindTraceDynamicIndexStatsAction $findTraceDynamicIndexStatsAction,
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
        $this->limit = 100;
    }

    public function handle(): array
    {
        $traces = $this->traceDynamicIndexRepository->find(
            limit: $this->limit,
            orderByCreatedAtDesc: true
        );

        /** @var array<string, TraceIndexInfoObject> $indexesInProcess */
        $indexesInProcess = Arr::keyBy(
            $this->findTraceDynamicIndexStatsAction->handle()->indexesInProcess,
            fn(TraceIndexInfoObject $index) => $index->name
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
                progress: ($indexesInProcess[$dto->name] ?? null)?->progress,
                created: $dto->created,
                error: $dto->error,
                actualUntilAt: $dto->actualUntilAt,
                createdAt: $dto->createdAt,
            ),
            $traces
        );
    }
}
