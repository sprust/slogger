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

    public static function fromStringValues(?string $field, ?string $direction): ?static
    {
        $directionEnum = $direction ? SortDirectionEnum::from($direction) : null;

        if (!$field) {
            return null;
        }

        return new static(
            field: $field,
            directionEnum: $directionEnum ?: SortDirectionEnum::Desc
        );
    }
}
