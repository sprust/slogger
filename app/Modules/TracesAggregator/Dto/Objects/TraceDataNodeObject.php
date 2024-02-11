<?php

namespace App\Modules\TracesAggregator\Dto\Objects;

class TraceDataNodeObject
{
    /**
     * @param TraceDataNodeObject[] $children
     */
    public function __construct(
        public string $key,
        public string|bool|int|float|null $value = null,
        public ?array $children = null
    ) {
    }
}
