<?php

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

readonly class TraceDynamicIndexFieldDto
{
    public function __construct(
        public string $fieldName
    ) {
    }
}
