<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

enum McpErrorCodeEnum: int
{
    case ParseError = -32700;
    case InvalidRequest = -32600;
    case MethodNotFound = -32601;
    case InvalidParams = -32602;
    case InternalError = -32603;
    case HeaderMismatch = -32020;
    case UnsupportedProtocolVersion = -32022;
}
