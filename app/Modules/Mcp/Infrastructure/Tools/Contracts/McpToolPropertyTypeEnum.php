<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

enum McpToolPropertyTypeEnum: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case StringList = 'string_list';
    case IntegerList = 'integer_list';
}
