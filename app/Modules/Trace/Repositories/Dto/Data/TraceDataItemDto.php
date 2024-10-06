<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

readonly class TraceDataItemDto
{
    public function __construct(
        public string $key,
        public mixed $value
    ) {
    }
}
