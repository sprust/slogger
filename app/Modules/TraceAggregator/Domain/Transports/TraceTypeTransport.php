<?php

namespace App\Modules\TraceAggregator\Domain\Transports;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTypeObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTypeDto;

class TraceTypeTransport
{
    public static function toObject(TraceTypeDto $dto): TraceTypeObject
    {
        return new TraceTypeObject(
            type: $dto->type,
            count: $dto->count,
        );
    }
}
