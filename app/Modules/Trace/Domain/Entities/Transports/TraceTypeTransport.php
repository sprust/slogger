<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\TraceTypeCountedObject;
use App\Modules\Trace\Repositories\Dto\TraceTypeDto;

class TraceTypeTransport
{
    public static function toObject(TraceTypeDto $dto): TraceTypeCountedObject
    {
        return new TraceTypeCountedObject(
            type: $dto->type,
            count: $dto->count,
        );
    }
}
