<?php

declare(strict_types=1);

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
