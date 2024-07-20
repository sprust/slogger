<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceIndexObject
{
    public function __construct(
        public string $name,
        public array $fields,
        public bool $inProcess,
        public bool $created,
        public Carbon $actualUntilAt,
        public Carbon $createdAt
    ) {
    }
}
