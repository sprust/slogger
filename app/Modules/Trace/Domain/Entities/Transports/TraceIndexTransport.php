<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\TraceIndexObject;
use App\Modules\Trace\Repositories\Dto\TraceIndexDto;

class TraceIndexTransport
{
    public static function toObject(TraceIndexDto $dto): TraceIndexObject
    {
        return new TraceIndexObject(
            name: $dto->name,
            fields: $dto->fields,
            inProcess: $dto->inProcess,
            created: $dto->created,
            actualUntilAt: $dto->actualUntilAt,
            createdAt: $dto->createdAt,
        );
    }
}
