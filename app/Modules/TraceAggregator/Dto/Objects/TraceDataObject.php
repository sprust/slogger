<?php

namespace App\Modules\TraceAggregator\Dto\Objects;

readonly class TraceDataObject
{
    /**
     * @param TraceDataObject[] $children
     */
    public function __construct(
        public string $key,
        public string|bool|int|float|null $value = null,
        public ?array $children = null
    ) {
    }
}
