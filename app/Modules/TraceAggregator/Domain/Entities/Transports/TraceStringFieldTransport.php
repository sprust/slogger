<?php

namespace App\Modules\TraceAggregator\Domain\Entities\Transports;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceStringFieldObject;
use App\Modules\TraceAggregator\Repositories\Dto\TraceStringFieldDto;

class TraceStringFieldTransport
{
    public static function toObject(TraceStringFieldDto $dto): TraceStringFieldObject
    {
        return new TraceStringFieldObject(
            name: $dto->name,
            count: $dto->count
        );
    }
}
