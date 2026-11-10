<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Responses;

use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

readonly class ReflectionPropertyDto
{
    public function __construct(
        public string $name,
        public string $type,
        public string $declaringClass,
        public bool $nullable,
        public ?OaListItemTypeAttribute $listItemAttribute,
    ) {
    }
}
