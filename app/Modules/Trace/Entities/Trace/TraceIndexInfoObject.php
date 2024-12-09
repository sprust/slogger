<?php

namespace App\Modules\Trace\Entities\Trace;

readonly class TraceIndexInfoObject
{
    public function __construct(
        public string $collectionName,
        public string $name,
        public float $progress
    ) {
    }
}
