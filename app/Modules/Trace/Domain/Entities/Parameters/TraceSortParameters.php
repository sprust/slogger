<?php

namespace App\Modules\Trace\Domain\Entities\Parameters;

use App\Modules\Common\Enums\SortDirectionEnum;

readonly class TraceSortParameters
{
    public function __construct(
        public string $field,
        public SortDirectionEnum $directionEnum
    ) {
    }
}
