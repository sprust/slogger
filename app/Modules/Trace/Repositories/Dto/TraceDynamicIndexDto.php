<?php

namespace App\Modules\Trace\Repositories\Dto;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexDto
{
    /**
     * @param TraceDynamicIndexFieldDto[] $fields
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
