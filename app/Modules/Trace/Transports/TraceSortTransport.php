<?php

namespace App\Modules\Trace\Transports;

use App\Modules\Trace\Parameters\TraceSortParameters;
use App\Modules\Trace\Repositories\Dto\TraceSortDto;

class TraceSortTransport
{
    /**
     * @return TraceSortDto[]
     */
    static public function fromObjects(array $objects): array
    {
        return array_map(
            fn(TraceSortParameters $object) => self::fromObject($object),
            $objects
        );
    }

    static private function fromObject(TraceSortParameters $object): TraceSortDto
    {
        return new TraceSortDto(
            field: $object->field,
            directionEnum: $object->directionEnum,
        );
    }
}
