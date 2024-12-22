<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Data;

readonly class TraceDataAdditionalFieldObject
{
    /**
     * @param array<string|bool|int|float|null> $values
     */
    public function __construct(
        public string $key,
        public array $values
    ) {
    }
}
