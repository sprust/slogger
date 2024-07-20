<?php

namespace App\Modules\Trace\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class TraceIndexDto
{
    /**
     * @param TraceIndexFieldDto[] $fields
     */
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
