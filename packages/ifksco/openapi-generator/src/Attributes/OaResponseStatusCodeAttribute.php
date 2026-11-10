<?php

namespace Ifksco\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute]
readonly class OaResponseStatusCodeAttribute
{
    public function __construct(public string $statusCode)
    {
    }
}
