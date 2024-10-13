<?php

namespace App\Modules\Trace\Transports;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;

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
