<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceDynamicIndexFieldDto
{
    public function __construct(
        public string $fieldName
    ) {
    }
}
