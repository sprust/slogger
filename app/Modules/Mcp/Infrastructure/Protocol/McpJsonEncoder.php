<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

readonly class McpJsonEncoder
{
    public const int FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    public function encode(mixed $value): string
    {
        return json_encode($value, self::FLAGS | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
