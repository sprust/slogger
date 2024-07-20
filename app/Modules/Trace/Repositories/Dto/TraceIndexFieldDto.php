<?php

namespace App\Modules\Trace\Repositories\Dto;

readonly class TraceIndexFieldDto
{
    public function __construct(
        public string $fieldName,
        public bool $isText = false
    ) {
    }
}
