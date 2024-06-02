<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\TraceStringFieldObject;
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
