<?php

namespace Ifksco\OpenApiGenerator\Json;

enum OaTypeEnum: string
{
    case Integer = 'integer';
    case Number = 'number';
    case String = 'string';
    case Boolean = 'boolean';
    case Array = 'array';
    case Object = 'object';
}
