<?php

namespace App\Modules\TracesAggregator\Dto\Parameters;

use App\Modules\TracesAggregator\Enums\TraceParentsSortFieldEnum;
use App\Services\Enums\SortDirectionEnum;

readonly class TraceParentsSortParameters
{
    public function __construct(
        public TraceParentsSortFieldEnum $fieldEnum,
        public SortDirectionEnum $directionEnum
    ) {
    }

    public static function fromStringValues(?string $field, ?string $direction): ?static
    {
        $fieldEnum     = $field ? TraceParentsSortFieldEnum::from($field) : null;
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
