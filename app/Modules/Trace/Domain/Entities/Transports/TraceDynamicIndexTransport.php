<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexFieldObject;
use App\Modules\Trace\Domain\Entities\Objects\TraceDynamicIndexObject;
use App\Modules\Trace\Domain\Services\TraceFieldTitlesService;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexFieldDto;

readonly class TraceDynamicIndexTransport
{
    public function __construct(private TraceFieldTitlesService $traceFieldTitlesService)
    {
    }

    public function fromDto(TraceDynamicIndexDto $dto): TraceDynamicIndexObject
    {
        return new TraceDynamicIndexObject(
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
        );
    }
}
