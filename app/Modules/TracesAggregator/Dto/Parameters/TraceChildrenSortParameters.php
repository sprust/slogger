<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Enums\TraceChildrenSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TraceChildrenSortParameters
{
    public function __construct(
        public TraceChildrenSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }

    public static function fromStringValues(?string $field, ?string $direction): ?static
    {
        $fieldEnum     = $field ? TraceChildrenSortFieldEnum::from($field) : null;
        $directionEnum = $direction ? SortDirectionEnum::from($direction) : null;

        if (!$fieldEnum) {
            return null;
        }

        return new static(
            fieldEnum: $fieldEnum,
            directionEnum: $directionEnum ?: SortDirectionEnum::Desc
        );
    }
}
