<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldIndicatorObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldObject;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampFieldIndicatorDto;

class TraceTimestampFieldTransport
{
    public static function toObject(TraceTimestampFieldDto $dto): TraceTimestampFieldObject
    {
        return new TraceTimestampFieldObject(
            field: $dto->field,
            indicators: array_map(
                fn(TraceTimestampFieldIndicatorDto $dto) => new TraceTimestampFieldIndicatorObject(
                    name: $dto->name,
                    value: $dto->value
                ),
                $dto->indicators
            ),
        );
    }
}
