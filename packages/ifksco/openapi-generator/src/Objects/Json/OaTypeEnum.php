<?php

namespace Ifksco\OpenApiGenerator\Objects\Json;

enum OaTypeEnum: string
{
    case Integer = 'integer';
    case Number  = 'number';
    case String  = 'string';
    case Boolean = 'boolean';
    case Array   = 'array';
    case Object  = 'object';
}
