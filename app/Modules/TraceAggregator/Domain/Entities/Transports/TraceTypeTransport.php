<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Transports;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTypeCountedObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTypeDto;

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
