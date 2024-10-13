<?php

namespace App\Modules\Trace\Parameters;

use App\Modules\Common\Enums\SortDirectionEnum;

readonly class TraceSortParameters
{
    public function __construct(
        public string $field,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
