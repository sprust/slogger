<?php

namespace Ifksco\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute]
readonly class OaSummaryAttribute
{
    public function __construct(private string $langKey)
    {
    }

    public function get(): string
    {
        return __($this->langKey);
    }
}
