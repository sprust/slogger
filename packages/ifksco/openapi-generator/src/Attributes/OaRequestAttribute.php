<?php

namespace Ifksco\OpenApiGenerator\Attributes;

use Attribute;
use Ifksco\OpenApiGenerator\Json\Schemes\OaRequestScheme;

#[Attribute]
class OaRequestAttribute
{
    public function __construct(public $contentType = OaRequestScheme::OA_CONTENT_TYPE_JSON)
    {
    }
}
