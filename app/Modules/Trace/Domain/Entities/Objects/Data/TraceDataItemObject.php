<?php

namespace App\Modules\Trace\Domain\Entities\Objects\Data;

readonly class TraceDataItemObject
{
    public function __construct(
        public string $id,
        public string $key
    ) {
    }
}
