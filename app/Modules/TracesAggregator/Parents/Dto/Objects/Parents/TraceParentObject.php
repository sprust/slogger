<?php

namespace App\Modules\TracesAggregator\Parents\Dto\Objects\Parents;

use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceObject;

readonly class TraceParentObject
{
    /**
     * @param TraceParentTypeObject[] $types
     */
    public function __construct(
        public TraceObject $parent,
        public array $types
    ) {
    }
}
