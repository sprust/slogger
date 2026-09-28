<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Enums;

enum McpIndexStatusEnum: string
{
    case Building = 'building';
    case Ready = 'ready';
    case Error = 'error';
    case NotFound = 'not_found';
}
