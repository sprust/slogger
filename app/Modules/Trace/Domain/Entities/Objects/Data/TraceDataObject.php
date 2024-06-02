<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Data;

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
