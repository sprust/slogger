<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Data;

readonly class TraceDataObject
{
    /**
     * @param TraceDataObject[]|null $children
     */
    public function __construct(
        public string $key,
        public string|bool|int|float|null $value,
        public ?array $children,
        public bool $canBeFiltered
    ) {
    }
}
