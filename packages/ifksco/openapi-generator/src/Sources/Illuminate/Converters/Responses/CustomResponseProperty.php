<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Responses;

class CustomResponseProperty
{
    public function __construct(
        public string $name,
        public mixed $type,
        public bool $nullable,
    ) {
    }
}
