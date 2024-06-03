<?php

namespace App\Modules\Trace\Repositories\Dto;

use App\Modules\Common\Enums\SortDirectionEnum;

readonly class TraceSortDto
{
    public function __construct(
        public string $field,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
