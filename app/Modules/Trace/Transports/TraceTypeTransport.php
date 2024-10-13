<?php

namespace App\Modules\Trace\Transports;

use App\Modules\Trace\Entities\Trace\TraceTypeCountedObject;
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
