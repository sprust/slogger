<?php

namespace App\Modules\TracesAggregator\Dto\Objects\Parents;

use App\Modules\TracesAggregator\Dto\Objects\TraceObject;

readonly class TracesAggregatorParentObject
{
    /**
     * @param TracesAggregatorParentTypeObject[] $types
     */
    public function __construct(
        public TraceObject $parent,
        public array $types
    ) {
    }
}
