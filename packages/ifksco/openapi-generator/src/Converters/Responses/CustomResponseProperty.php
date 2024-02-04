<?php

namespace Ifksco\OpenApiGenerator\Converters\Responses;

class CustomResponseProperty
{
    public function __construct(
        public string $name,
        public mixed $type,
        public bool $nullable,
    ) {
    }
}
